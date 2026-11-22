<?php

namespace App\Models\Concerns;

use App\Models\Team;
use App\Support\CurrentTeam;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Multi-tenancy for a model:
 *
 * - every query is automatically limited to the current team (global scope),
 *   which also makes route model binding return 404 for other teams' rows;
 * - new rows get the current team's id automatically.
 *
 * When no team is set (console commands, seeders, tests outside a request)
 * nothing is filtered. Web and API routes always run the "team" middleware,
 * which refuses the request if the user has no team, so tenant routes can
 * never run unfiltered.
 */
trait BelongsToTeam
{
    public static function bootBelongsToTeam(): void
    {
        static::addGlobalScope('team', function (Builder $query) {
            $teamId = app(CurrentTeam::class)->id();

            if ($teamId !== null) {
                $query->where($query->qualifyColumn('team_id'), $teamId);
            }
        });

        static::creating(function (Model $model) {
            $model->team_id ??= app(CurrentTeam::class)->id();
        });
    }

    /**
     * @return BelongsTo<Team, $this>
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }
}
