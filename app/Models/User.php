<?php

namespace App\Models;

use App\Support\CurrentTeam;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use InvalidArgumentException;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

// Roles are not columns on the user: they live per team (spatie/laravel-permission),
// and current_team_id is set only through switchTeam(). Neither is mass-assignable.
#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token', 'two_factor_secret', 'two_factor_recovery_codes'])]
class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, HasRoles, Notifiable, TwoFactorAuthenticatable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Give the user a role in a specific team (replacing any role they had there).
     */
    public function setTeamRole(Team $team, string $role): void
    {
        $this->inTeam($team, fn () => $this->syncRoles([$role]));
    }

    /**
     * The user's role in a specific team, or null when they have none.
     */
    public function teamRole(Team $team): ?string
    {
        return $this->inTeam($team, fn () => $this->roles()->value('name'));
    }

    /**
     * Run a callback with roles and permissions looked up for $team.
     */
    private function inTeam(Team $team, callable $callback): mixed
    {
        $currentTeam = app(CurrentTeam::class);
        $previous = $currentTeam->get();
        $currentTeam->set($team);

        try {
            return $callback();
        } finally {
            $currentTeam->set($previous);
            // Loaded roles/permissions belonged to $team; don't reuse them for another team.
            $this->unsetRelation('roles')->unsetRelation('permissions');
        }
    }

    /**
     * Every team this is user is a member of.
     *
     * @return BelongsToMany<Team, $this>
     */
    public function teams(): BelongsToMany
    {
        return $this->belongsToMany(Team::class)->withTimestamps();
    }

    /**
     * The team the user is currently working in.
     *
     * @return BelongsTo<Team, $this>
     */
    public function currentTeam(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'current_team_id');
    }

    public function belongsToTeam(Team $team): bool
    {
        return $this->teams()->where('team_id', $team->id)->exists();
    }

    /**
     * Change the current team. Only teams the user is a member of are allowed
     */
    public function switchTeam(Team $team): void
    {
        if (! $this->belongsToTeam($team)) {
            throw new InvalidArgumentException('The user is not a member of the team.');
        }

        // forceFill: current_team_id is deliberately not mass-assignable.
        $this->forceFill(['current_team_id' => $team->id])->save();

        // Keep the loaded relation in step, or this object would keep
        // reporting the previous team as current.
        $this->setRelation('currentTeam', $team);
    }
}
