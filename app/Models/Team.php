<?php

namespace App\Models;

use App\Models\Activity as TeamActivity;
use Database\Factories\TeamFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Spatie\Activitylog\Contracts\Activity;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

#[Fillable(['name', 'slug', 'plan'])]
class Team extends Model
{
    /** @use HasFactory<TeamFactory> */
    use HasFactory, LogsActivity;

    /**
     * Mirror the database default, so a team created in this request
     * already knows it's on the Free plan.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'plan' => 'free',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'onboarding_dismissed_at' => 'datetime',
        ];
    }

    /**
     * Audit log: renaming the shop or changing its plan.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('team')
            ->logOnly(['name', 'plan'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn (string $event) => "{$event} the shop settings");
    }

    public function tapActivity(Activity $activity, string $eventName): void
    {
        if ($activity instanceof TeamActivity) {
            $activity->team_id = $this->id;
        }
    }

    /**
     * Only changes are logged; creating a team isn't worth an entry in its own log.
     *
     * @var array<int, string>
     */
    protected static $recordEvents = ['updated'];

    /**
     * Give every team a unique slug automatically, e.g. "kedai-ali-x7k2pq".
     */
    protected static function booted(): void
    {
        static::creating(function (Team $team) {
            $team->slug ??= Str::slug($team->name).'-'.Str::lower(Str::random(6));
        });
    }

    /**
     * The users who are members of this team.
     *
     * @return BelongsToMany<User, $this>
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->withTimestamps();
    }

    /**
     * Members who have one of the given roles in this team, e.g. the
     * owners and admins who should hear about low stock.
     *
     * @param  array<int, string>  $roles
     * @return EloquentCollection<int, User>
     */
    public function membersWithRole(array $roles): EloquentCollection
    {
        $userIds = DB::table('model_has_roles')
            ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
            ->where('model_has_roles.team_id', $this->id)
            ->where('model_has_roles.model_type', (new User)->getMorphClass())
            ->whereIn('roles.name', $roles)
            ->pluck('model_has_roles.model_id');

        return $this->users()->whereIn('users.id', $userIds)->get();
    }

    /**
     * @return HasMany<TeamInvitation, $this>
     */
    public function invitations(): HasMany
    {
        return $this->hasMany(TeamInvitation::class);
    }

    /**
     * @return HasMany<Category, $this>
     */
    public function categories(): HasMany
    {
        return $this->hasMany(Category::class);
    }

    /**
     * @return HasMany<Product, $this>
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }
}
