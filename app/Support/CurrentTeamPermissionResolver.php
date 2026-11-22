<?php

namespace App\Support;

use App\Models\Team;
use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\Contracts\PermissionsTeamResolver;

/**
 * Tells spatie/laravel-permission which team's roles to use. Instead of
 * keeping its own copy, it reads (and writes) CurrentTeam, so the team that
 * filters the data is always the team whose roles are checked.
 */
class CurrentTeamPermissionResolver implements PermissionsTeamResolver
{
    public function setPermissionsTeamId(int|string|Model|null $id): void
    {
        $team = match (true) {
            $id instanceof Team => $id,
            $id === null => null,
            default => Team::find($id),
        };

        app(CurrentTeam::class)->set($team);
    }

    public function getPermissionsTeamId(): int|string|null
    {
        return app(CurrentTeam::class)->id();
    }
}
