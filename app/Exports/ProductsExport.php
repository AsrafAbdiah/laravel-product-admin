<?php

namespace App\Exports;

use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

/**
 * Exports products to Excel. FromQuery makes Laravel-Excel read the
 * rows in chunks, so large tables don't get loaded into memory at once.
 */
class ProductsExport implements FromQuery, ShouldAutoSize, WithHeadings, WithMapping
{
    use Exportable;

    /**
     * @param  Builder<Product>  $query  an already-filtered product query
     */
    public function __construct(private Builder $query) {}

    /**
     * @return Builder<Product>
     */
    public function query(): Builder
    {
        return $this->query;
    }

    /**
     * @return array<int, string>
     */
    public function headings(): array
    {
        return ['ID', 'Name', 'Category', 'Description', 'Price', 'Stock', 'Status', 'Created At'];
    }

    /**
     * @param  Product  $row
     * @return array<int, mixed>
     */
    public function map(mixed $row): array
    {
        return [
            $row->id,
            $row->name,
            $row->category?->name,
            $row->description,
            $row->price,
            $row->stock,
            $row->enabled ? 'Enabled' : 'Disabled',
            $row->created_at?->format('Y-m-d H:i'),
        ];
    }
}
