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
}
