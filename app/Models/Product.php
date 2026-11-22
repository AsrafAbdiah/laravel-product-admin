<?php

namespace App\Models;

use App\Models\Activity as TeamActivity;
use App\Models\Concerns\BelongsToTeam;
use App\Observers\ProductObserver;
use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Contracts\Activity;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * @property int $team_id
 */
// "image_path" is fillable for the service, but no Form Request accepts it,
// so a client can't point a product at an arbitrary file.
#[Fillable(['category_id', 'name', 'description', 'image_path', 'price', 'stock', 'enabled'])]
#[ObservedBy(ProductObserver::class)]
class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use BelongsToTeam, HasFactory, LogsActivity, SoftDeletes;

    /**
     * Audit log: what to record when a product is created, changed,
     * moved to the trash, restored or deleted for good.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('products')
            ->logOnly(['name', 'category.name', 'description', 'price', 'stock', 'enabled', 'image_path'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn (string $event) => match ($event) {
                'deleted' => $this->isForceDeleting() ? "permanently deleted product \"{$this->name}\"" : "moved product \"{$this->name}\" to the trash",
                default => "{$event} product \"{$this->name}\"",
            });
    }

    /**
     * Log entries belong to the product's own team, even when written
     * outside a request (seeders, queued jobs).
     */
    public function tapActivity(Activity $activity, string $eventName): void
    {
        if ($activity instanceof TeamActivity) {
            $activity->team_id = $this->team_id;
        }
    }

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
     * Every recorded change to this product's stock.
     *
     * @return HasMany<StockMovement, $this>
     */
    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    /**
     * Public URL of the product image, or null when there is none.
     *
     * Built from the address the app is being visited on (asset()), not the
     * configured APP_URL, so it is always "same origin" for the page and
     * allowed by the Content-Security-Policy (img-src 'self').
     *
     * @return Attribute<?string, never>
     */
    protected function imageUrl(): Attribute
    {
        return Attribute::get(
            fn () => $this->image_path ? asset('storage/'.$this->image_path) : null
        );
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
