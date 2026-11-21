<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Database\Eloquent\Collection;

/**
 * Numbers for the dashboard. Everything is computed by the database
 * (COUNT / SUM), so the cost stays the same no matter how many
 * products there are: a handful of small queries in total.
 */
class DashboardService
{
    /**
     * @return array{products: int, enabled: int, disabled: int, low_stock: int, in_trash: int, inventory_value: string}
     */
    public function totals(): array
    {
        // One query for five numbers. The soft-delete scope still applies,
        // so trashed products are left out automatically.
        $row = Product::query()
            ->toBase()
            ->selectRaw('COUNT(*) as products')
            ->selectRaw('COALESCE(SUM(CASE WHEN enabled = 1 THEN 1 ELSE 0 END), 0) as enabled')
            ->selectRaw('COALESCE(SUM(CASE WHEN stock <= ? THEN 1 ELSE 0 END), 0) as low_stock', [Product::LOW_STOCK_THRESHOLD])
            ->selectRaw('COALESCE(SUM(price * stock), 0) as inventory_value')
            ->first();

        return [
            'products' => (int) $row->products,
            'enabled' => (int) $row->enabled,
            'disabled' => (int) $row->products - (int) $row->enabled,
            'low_stock' => (int) $row->low_stock,
            'in_trash' => Product::onlyTrashed()->count(),
            // Money stays a string with 2 decimals, like product prices.
            'inventory_value' => number_format((float) $row->inventory_value, 2, '.', ''),
        ];
    }

    /**
     * Categories with their active product counts, biggest first.
     *
     * @return Collection<int, Category>
     */
    public function productsPerCategory(): Collection
    {
        return Category::select(['id', 'name'])
            ->withCount('products')
            ->orderByDesc('products_count')
            ->orderBy('name')
            ->get();
    }

    /**
     * The products closest to running out.
     *
     * @return Collection<int, Product>
     */
    public function lowStockProducts(int $limit = 5): Collection
    {
        return Product::lowStock(true)
            ->with('category:id,name')
            ->orderBy('stock')
            ->orderBy('name')
            ->limit($limit)
            ->get();
    }

    /**
     * The latest stock changes across all products.
     *
     * @return Collection<int, StockMovement>
     */
    public function recentStockMovements(int $limit = 8): Collection
    {
        return StockMovement::with(['product:id,name,deleted_at', 'user:id,name'])
            ->latest('created_at')
            ->latest('id')
            ->limit($limit)
            ->get();
    }
}
