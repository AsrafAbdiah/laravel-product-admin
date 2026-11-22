<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * An invitation to join a team with a given role.
 *
 * Deliberately NOT team-scoped (no BelongsToTeam): the person accepting it
 * is usually working in a different team, and must still be able to find it.
 *
 * @property Carbon $expires_at
 */
#[Fillable(['email', 'role', 'token_hash', 'invited_by', 'expires_at'])]
class TeamInvitation extends Model
{
    use Prunable;

    public const VALID_FOR_DAYS = 7;

    /**
     * "php artisan model:prune" (scheduled daily) removes invitations that
     * expired more than 30 days ago; recent ones stay so the team page can
     * still show them as expired.
     *
     * @return Builder<self>
     */
    public function prunable(): Builder
    {
        return static::where('expires_at', '<', now()->subDays(30));
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Team, $this>
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function inviter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by');
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public static function hashToken(string $token): string
    {
        return hash('sha256', $token);
    }

    public static function findByToken(string $token): ?self
    {
        return static::where('token_hash', static::hashToken($token))->first();
    }
}
