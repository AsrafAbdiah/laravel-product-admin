<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTeam;
use Database\Factories\CategoryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\Contracts\Activity;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

#[Fillable(['name'])]
class Category extends Model
{
    /** @use HasFactory<CategoryFactory> */
    use BelongsToTeam, HasFactory, LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('categories')
            ->logOnly(['name'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn (string $event) => "{$event} category \"{$this->name}\"");
    }

    public function tapActivity(Activity $activity, string $eventName): void
    {
        $activity->team_id = $this->team_id;
    }

    /**
     * A category has many products.
     *
     * @return HasMany<Product, $this>
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    /**
     * Whether any product still points at this category. Soft-deleted products
     * count too: their rows still exist, so the foreign key would block the delete.
     */
    public function hasProducts(): bool
    {
        return $this->products()->withTrashed()->exists();
    }
}
