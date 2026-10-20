<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

/**
 * Product business logic shared by the API and the web dashboard,
 * so both stay consistent and the controllers stay thin.
 */
class ProductService
{
    /**
     * Base query with filters applied and the category eager loaded
     * (only the columns we need), which avoids N+1 queries.
     *
     * @param  array{status?: ?string, category_id?: ?int, search?: ?string}  $filters
     * @return Builder<Product>
     */
    public function query(array $filters = []): Builder
    {
        return Product::query()
            ->with('category:id,name')
            ->status($filters['status'] ?? null)
            ->inCategory($filters['category_id'] ?? null)
            ->search($filters['search'] ?? null)
            ->lowStock($filters['low_stock'] ?? false)
            ->sorted($filters['sort'] ?? null);
    }

    /**
     * @param  array{status?: ?string, category_id?: ?int, search?: ?string, per_page?: int}  $filters
     */
    public function paginate(array $filters = []): LengthAwarePaginator
    {
        return $this->query($filters)
            ->paginate($filters['per_page'] ?? 15)
            ->withQueryString();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Product
    {
        return Product::create($data)->load('category:id,name');
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Product $product, array $data): Product
    {
        $product->update($data);

        return $product->load('category:id,name');
    }

    /**
     * Soft delete a single product.
     */
    public function delete(Product $product): void
    {
        $product->delete();
    }

    /**
     * Soft delete many products in a single query.
     *
     * @param  array<int, int>  $ids
     * @return int number of products deleted
     */
    public function bulkDelete(array $ids): int
    {
        return Product::whereIn('id', $ids)->delete();
    }
}
