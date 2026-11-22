<?php

namespace App\Http\Middleware;

use App\Support\CurrentTeam;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetCurrentTeam
{
    public function __construct(private CurrentTeam $currentTeam) {}

    /**
     * Work out which team this request belongs to. Runs after the auth
     * middleware, so the user is already known.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user) {
            $team = $user->currentTeam;

            // If the user has no current team, or was removed from it,
            // fall back to the first team they still belong to.
            if (! $team || ! $user->belongsToTeam($team)) {
                $team = $user->teams()->orderBy('teams.id')->first();

                if ($team) {
                    $user->switchTeam($team);
                }
            }

            // Fail closed: without a team, team-scoped queries would not be
            // filtered, so a user with no team is not let in at all.
            abort_if($team === null, Response::HTTP_FORBIDDEN, 'You are not a member of any team.');

            $this->currentTeam->set($team);
        }

        return $next($request);
    }
}
