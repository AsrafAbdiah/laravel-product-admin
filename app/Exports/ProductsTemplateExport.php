<?php

namespace App\Exports;

use App\Models\Category;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;

/**
 * An empty import file with the expected column headings and one
 * example row, so people can see the format before filling it in.
 */
class ProductsTemplateExport implements FromArray, ShouldAutoSize, WithHeadings, WithStrictNullComparison
{
    /**
     * @return array<int, string>
     */
    public function headings(): array
    {
        return ['Name', 'Category', 'Description', 'Price', 'Stock', 'Status'];
    }

    /**
     * @return array<int, array<int, mixed>>
     */
    public function array(): array
    {
        return [
            ['Example: Wireless Mouse (delete this row)', Category::orderBy('name')->value('name') ?? 'Electronics', 'Optional description', 49.90, 25, 'enabled'],
        ];
    }
}
