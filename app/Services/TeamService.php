<?php

namespace App\Services;

use App\Models\Team;
use App\Models\TeamInvitation;
use App\Models\User;
use App\Notifications\TeamInvitationNotification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

/**
 * Team membership: invitations, roles and removing members.
 */
class TeamService
{
    /**
     * Every member's role in the team, keyed by user id, in ONE query
     * (instead of asking each member for their role, which would be N+1).
     *
     * @return Collection<int, string>
     */
    public function memberRoles(Team $team): Collection
    {
        return DB::table('model_has_roles')
            ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
            ->where('model_has_roles.team_id', $team->id)
            ->where('model_has_roles.model_type', (new User)->getMorphClass())
            ->pluck('roles.name', 'model_has_roles.model_id');
    }

    /**
     * Invite someone by email. The token is random; only its hash is
     * stored, and the plain token goes out in the email link.
     */
    public function invite(Team $team, User $inviter, string $email, string $role): TeamInvitation
    {
        $token = Str::random(48);

        $invitation = $team->invitations()->create([
            'email' => mb_strtolower($email),
            'role' => $role,
            'token_hash' => TeamInvitation::hashToken($token),
            'invited_by' => $inviter->id,
            'expires_at' => now()->addDays(TeamInvitation::VALID_FOR_DAYS),
        ]);

        Notification::route('mail', $invitation->email)
            ->notify(new TeamInvitationNotification($invitation, $token));

        return $invitation;
    }

    /**
     * Join the team with the invited role and switch to it.
     */
    public function accept(TeamInvitation $invitation, User $user): Team
    {
        return DB::transaction(function () use ($invitation, $user) {
            $team = $invitation->team;

            $user->teams()->syncWithoutDetaching([$team->id]);
            $user->setTeamRole($team, $invitation->role);
            $user->switchTeam($team);
            $invitation->delete();

            return $team;
        });
    }

    public function changeRole(Team $team, User $member, string $role): void
    {
        $member->setTeamRole($team, $role);
    }

    /**
     * Take the member out of the team: their role there and the membership.
     * If it was their current team, the middleware moves them to another
     * team on their next request.
     */
    public function removeMember(Team $team, User $member): void
    {
        DB::transaction(function () use ($team, $member) {
            $member->setTeamRole($team, null);
            $member->teams()->detach($team);

            if ($member->current_team_id === $team->id) {
                $member->forceFill(['current_team_id' => null])->save();
            }
        });
    }
}
