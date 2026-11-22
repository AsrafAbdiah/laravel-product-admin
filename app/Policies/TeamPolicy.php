<?php

namespace App\Policies;

use App\Models\Team;
use App\Models\User;
use App\Support\Roles;

class TeamPolicy
{
    /**
     * Any member can see who else is in the team.
     */
    public function view(User $user, Team $team): bool
    {
        return $user->belongsToTeam($team);
    }

    /**
     * Rename the shop, invite people, change roles.
     */
    public function manageMembers(User $user, Team $team): bool
    {
        return $user->belongsToTeam($team) && $user->checkPermissionTo('team.manage');
    }

    public function update(User $user, Team $team): bool
    {
        return $this->manageMembers($user, $team);
    }

    /**
     * Change someone's role or remove them. Nobody can change the owner,
     * and nobody can change themselves (so an admin can't lock everyone out
     * by demoting the last admin, i.e. themselves, by accident).
     */
    public function changeMember(User $user, Team $team, User $member): bool
    {
        return $this->manageMembers($user, $team)
            && $member->isNot($user)
            && $member->teamRole($team) !== Roles::OWNER;
    }
}
