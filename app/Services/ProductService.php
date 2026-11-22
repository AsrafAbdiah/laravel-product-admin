<?php

namespace App\Services;

use App\Exceptions\ProductNotInTrashException;
use App\Imports\ProductsImport;
use App\Jobs\ExportProductsJob;
use App\Jobs\ImportProductsJob;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\Team;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

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
     * @param  array{status?: ?string, category_id?: ?int, search?: ?string, low_stock?: bool, sort?: ?string, per_page?: int}  $filters
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
        $names = Product::whereIn('id', $ids)->pluck('name');
        $deleted = Product::whereIn('id', $ids)->delete();

        // One query, so no per-product model events: write one summary entry instead.
        activity('products')
            ->event('bulk_deleted')
            ->withProperties(['ids' => $ids, 'names' => $names])
            ->log("moved {$deleted} products to the trash");

        return $deleted;
    }

    /**
     * Create products from a spreadsheet. Valid rows are imported and
     * invalid ones are reported back. The Context value labels the stock
     * history entries as "imported", and only lasts for this call.
     *
     * @return array{imported: int, failed: array<int, array{row: int, errors: array<int, string>}>}
     */
    public function import(UploadedFile $file): array
    {
        return $this->runImport($file, null, $file->getClientOriginalName());
    }

    /**
     * Same as import(), for a file saved earlier (queued imports).
     *
     * @return array{imported: int, failed: array<int, array{row: int, errors: array<int, string>}>}
     */
    public function importStoredFile(string $path, string $disk, string $originalName): array
    {
        return $this->runImport($path, $disk, $originalName);
    }

    /**
     * Is this upload big enough to import in the background?
     */
    public function shouldQueueImport(UploadedFile $file): bool
    {
        return $file->getSize() > config('bandaros.queue_imports_over_kb') * 1024;
    }

    /**
     * Save the upload privately and import it on the queue.
     */
    public function queueImport(UploadedFile $file, Team $team, User $user): void
    {
        $path = $file->storeAs("imports/{$team->id}", Str::uuid().'.'.$file->getClientOriginalExtension(), 'local');

        ImportProductsJob::dispatch($team->id, $user->id, $path, $file->getClientOriginalName());
    }

    /**
     * Is this export big enough to build in the background?
     *
     * @param  array<string, mixed>  $filters
     */
    public function shouldQueueExport(array $filters): bool
    {
        return $this->query($filters)->count() > config('bandaros.queue_exports_over_rows');
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function queueExport(array $filters, Team $team, User $user): void
    {
        ExportProductsJob::dispatch($team->id, $user->id, $filters, (string) Str::uuid());
    }

    /**
     * @return array{imported: int, failed: array<int, array{row: int, errors: array<int, string>}>}
     */
    private function runImport(UploadedFile|string $file, ?string $disk, string $fileName): array
    {
        $import = new ProductsImport;

        // One audit entry for the whole import rather than one per row.
        activity()->withoutLogs(fn () => Context::scope(
            fn () => Excel::import($import, $file, $disk),
            ['stock_reason' => StockMovement::REASON_IMPORTED],
        ));

        $result = [
            'imported' => $import->importedCount(),
            'failed' => $import->failedRows(),
        ];

        activity('products')
            ->event('imported')
            ->withProperties(['file' => $fileName, 'imported' => $result['imported'], 'skipped' => count($result['failed'])])
            ->log("imported {$result['imported']} products from {$fileName}");

        return $result;
    }

    /**
     * A product's stock history, newest first, with who made each change.
     */
    public function stockHistory(Product $product, int $perPage = 20): LengthAwarePaginator
    {
        return $product->stockMovements()
            ->with('user:id,name')
            ->latest('created_at')
            ->latest('id')
            ->paginate($perPage);
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
