<?php

namespace App\Models;

use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['category_id', 'name', 'description', 'price', 'stock', 'enabled'])]
class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use HasFactory, SoftDeletes;

    /**
     * Products at or below this stock level count as "low stock".
     */
    public const LOW_STOCK_THRESHOLD = 10;

    /**
     * Mirror the database default so a freshly created model reports it too.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'enabled' => true,
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'stock' => 'integer',
            'enabled' => 'boolean',
        ];
    }

    /**
     * A product belongs to one category.
     *
     * @return BelongsTo<Category, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Filter by status: "enabled" or "disabled". Any other value is ignored.
     */
    #[Scope]
    protected function status(Builder $query, ?string $status): void
    {
        match ($status) {
            'enabled' => $query->where('enabled', true),
            'disabled' => $query->where('enabled', false),
            default => null,
        };
    }

    /**
     * Filter by category id when one is given.
     */
    #[Scope]
    protected function inCategory(Builder $query, ?int $categoryId): void
    {
        $query->when($categoryId, fn (Builder $q) => $q->where('category_id', $categoryId));
    }

    /**
     * Simple name search when a term is given.
     */
    #[Scope]
    protected function search(Builder $query, ?string $term): void
    {
        $query->when($term, fn (Builder $q) => $q->where('name', 'like', '%'.$term.'%'));
    }

    /**
     * Only products that are low in stock.
     */
    #[Scope]
    protected function lowStock(Builder $query, bool $onlyLowStock = false): void
    {
        $query->when($onlyLowStock, fn (Builder $q) => $q->where('stock', '<=', self::LOW_STOCK_THRESHOLD));
    }

    /**
     * Sort by a whitelisted field. A leading "-" means descending.
     * Falls back to newest first. "id" added as a tie-breaker so paging stays consistent.
     */
    #[Scope]
    protected function sorted(Builder $query, ?string $sort): void
    {
        if (! $sort) {
            $query->latest('id');

            return;
        }

        $direction = str_starts_with($sort, '-') ? 'desc' : 'asc';

        $query->orderBy(ltrim($sort, '-'), $direction)->orderByDesc('id');
    }
}
