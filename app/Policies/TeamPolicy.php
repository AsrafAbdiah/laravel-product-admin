<?php

namespace App\Policies;

use App\Models\Team;
use App\Models\User;
use App\Support\PlanLimits;
use App\Support\Roles;
use Illuminate\Auth\Access\Response;

class TeamPolicy
{
    public function __construct(private PlanLimits $limits) {}

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
     * Inviting needs the permission AND a free seat on the plan.
     */
    public function invite(User $user, Team $team): Response
    {
        if (! $this->manageMembers($user, $team)) {
            return Response::deny();
        }

        if (! $this->limits->canAddMembers($team)) {
            $max = $this->limits->plan($team)['max_members'];

            return Response::deny("Your {$this->limits->plan($team)['name']} plan allows {$max} members (including pending invitations). The shop owner can upgrade to Pro.");
        }

        return Response::allow();
    }

    /**
     * Only the owner chooses the plan.
     */
    public function managePlan(User $user, Team $team): bool
    {
        return $user->teamRole($team) === Roles::OWNER;
    }

    /**
     * The audit log shows everything everyone did, so only managers see it.
     */
    public function viewActivity(User $user, Team $team): bool
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
