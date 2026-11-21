<?php

namespace App\Services;

use App\Exceptions\ProductNotInTrashException;
use App\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

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
     * @param  array<string, mixed>  $data  validated input, may include an "image" upload
     */
    public function create(array $data): Product
    {
        return Product::create($this->withImage($data))->load('category:id,name');
    }

    /**
     * @param  array<string, mixed>  $data  validated input, may include "image" and "remove_image"
     */
    public function update(Product $product, array $data): Product
    {
        $oldImage = $product->image_path;

        $product->update($this->withImage($data));

        // Only delete the old file once the database row points somewhere else.
        if ($oldImage && $oldImage !== $product->image_path) {
            Storage::disk('public')->delete($oldImage);
        }

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

    /**
     * Products in the trash, most recently deleted first.
     *
     * @param  array{search?: ?string, per_page?: int}  $filters
     */
    public function trashed(array $filters = []): LengthAwarePaginator
    {
        return Product::onlyTrashed()
            ->with('category:id,name')
            ->search($filters['search'] ?? null)
            ->latest('deleted_at')
            ->latest('id')
            ->paginate($filters['per_page'] ?? 15)
            ->withQueryString();
    }

    /**
     * Bring a product back from the trash.
     *
     * @throws ProductNotInTrashException
     */
    public function restore(Product $product): Product
    {
        $this->ensureTrashed($product);

        $product->restore();

        return $product->load('category:id,name');
    }

    /**
     * Permanently delete a product. Only allowed from the trash, so a
     * product always goes through a soft delete first.
     *
     * @throws ProductNotInTrashException
     */
    public function forceDelete(Product $product): void
    {
        $this->ensureTrashed($product);

        $product->forceDelete();

        if ($product->image_path) {
            Storage::disk('public')->delete($product->image_path);
        }
    }

    /**
     * Turn the "image" upload and "remove_image" flag into an image_path
     * value. A new upload wins over remove_image.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function withImage(array $data): array
    {
        $image = $data['image'] ?? null;
        $remove = (bool) ($data['remove_image'] ?? false);
        unset($data['image'], $data['remove_image']);

        if ($image instanceof UploadedFile) {
            $data['image_path'] = $image->store('products', 'public');
        } elseif ($remove) {
            $data['image_path'] = null;
        }

        return $data;
    }

    private function ensureTrashed(Product $product): void
    {
        if (! $product->trashed()) {
            throw new ProductNotInTrashException;
        }
    }
}
