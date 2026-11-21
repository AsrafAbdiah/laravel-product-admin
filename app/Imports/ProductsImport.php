<?php

namespace App\Imports;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Validators\Failure;

/**
 * Creates products from a spreadsheet with the columns:
 * Name, Category, Description, Price, Stock, Status.
 *
 * Rows that fail validation are skipped and collected (SkipsFailures),
 * so one bad row doesn't stop the rest of the file from importing.
 */
class ProductsImport implements SkipsOnFailure, ToModel, WithChunkReading, WithHeadingRow, WithValidation
{
    use Importable, SkipsFailures;

    /**
     * Category ids keyed by lower-case name, loaded once so each row
     * doesn't need its own query.
     *
     * @var Collection<string, int>
     */
    private Collection $categoryIds;

    private int $imported = 0;

    public function __construct()
    {
        $this->categoryIds = Category::pluck('id', 'name')
            ->mapWithKeys(fn (int $id, string $name) => [mb_strtolower($name) => $id]);
    }

    /**
     * Tidy each row before validation: trim text, match the category
     * case-insensitively and default the status to "enabled".
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function prepareForValidation(array $data, int $index): array
    {
        foreach (['name', 'category', 'description', 'status'] as $key) {
            if (isset($data[$key]) && is_string($data[$key])) {
                $data[$key] = trim($data[$key]);
            }
        }

        $data['status'] = mb_strtolower((string) ($data['status'] ?? '')) ?: 'enabled';
        $data['category_key'] = mb_strtolower((string) ($data['category'] ?? ''));

        return $data;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'category' => ['required'],
            'category_key' => [Rule::in($this->categoryIds->keys()->all())],
            'description' => ['nullable', 'string', 'max:5000'],
            'price' => ['required', 'numeric', 'decimal:0,2', 'min:0', 'max:99999999.99'],
            'stock' => ['required', 'integer', 'min:0', 'max:4294967295'],
            'status' => ['in:enabled,disabled'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function customValidationMessages(): array
    {
        return [
            'category_key.in' => 'The category does not exist. Create it first, or fix the spelling.',
            'status.in' => 'The status must be "enabled" or "disabled".',
        ];
    }

    /**
     * Show "category" in errors instead of the internal "category_key".
     *
     * @return array<string, string>
     */
    public function customValidationAttributes(): array
    {
        return ['category_key' => 'category'];
    }

    /**
     * Only called for rows that passed validation. Returning an unsaved
     * model lets Laravel-Excel save it, which fires the model events, so
     * the stock history records each imported product.
     *
     * @param  array<string, mixed>  $row
     */
    public function model(array $row): Product
    {
        $this->imported++;

        return new Product([
            'category_id' => $this->categoryIds[$row['category_key']],
            'name' => $row['name'],
            'description' => $row['description'] ?? null,
            'price' => $row['price'],
            'stock' => (int) $row['stock'],
            'enabled' => $row['status'] === 'enabled',
        ]);
    }

    public function chunkSize(): int
    {
        return 500;
    }

    public function importedCount(): int
    {
        return $this->imported;
    }

    /**
     * Skipped rows grouped by spreadsheet row number.
     *
     * @return array<int, array{row: int, errors: array<int, string>}>
     */
    public function failedRows(): array
    {
        return $this->failures()
            ->groupBy(fn (Failure $failure) => $failure->row())
            ->map(fn (Collection $failures, int $row) => [
                'row' => $row,
                'errors' => $failures->flatMap(fn (Failure $failure) => $failure->errors())->values()->all(),
            ])
            ->sortKeys()
            ->values()
            ->all();
    }
}
