<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTeam;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One change to a product's stock level. Rows are only ever inserted,
 * never edited, so there is no updated_at column.
 */
#[Fillable(['team_id', 'product_id', 'user_id', 'old_stock', 'new_stock', 'change', 'reason'])]
class StockMovement extends Model
{
    use BelongsToTeam;

    public const REASON_CREATED = 'created';

    public const REASON_UPDATED = 'updated';

    public const REASON_IMPORTED = 'imported';

    public const UPDATED_AT = null;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'old_stock' => 'integer',
            'new_stock' => 'integer',
            'change' => 'integer',
            'created_at' => 'datetime',
        ];
    }

    /**
     * The product, even if it has since been moved to the trash.
     *
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class)->withTrashed();
    }

    /**
     * The admin who made the change. Null for system changes such as seeding.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Human-readable reason for the dashboard.
     */
    public function reasonLabel(): string
    {
        return match ($this->reason) {
            self::REASON_CREATED => 'Created',
            self::REASON_UPDATED => 'Edited',
            self::REASON_IMPORTED => 'Imported',
            default => ucfirst($this->reason),
        };
    }
}
