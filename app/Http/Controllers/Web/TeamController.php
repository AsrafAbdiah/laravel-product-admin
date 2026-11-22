<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\InviteMemberRequest;
use App\Models\Team;
use App\Models\TeamInvitation;
use App\Models\User;
use App\Services\TeamService;
use App\Support\CurrentTeam;
use App\Support\Roles;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * The current team's settings: its name, members, roles and invitations.
 */
class TeamController extends Controller
{
    public function __construct(private TeamService $teams, private CurrentTeam $currentTeam) {}

    public function show(): View
    {
        $team = $this->currentTeam->get();
        $this->authorize('view', $team);

        return view('team.show', [
            'team' => $team,
            'members' => $team->users()->orderBy('name')->get(),
            'roles' => $this->teams->memberRoles($team),
            'invitations' => $team->invitations()->with('inviter:id,name')->latest()->get(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $team = $this->currentTeam->get();
        $this->authorize('update', $team);

        $team->update($request->validate(['name' => ['required', 'string', 'max:100']]));

        return back()->with('success', 'Shop name updated.');
    }

    public function invite(InviteMemberRequest $request): RedirectResponse
    {
        $invitation = $this->teams->invite(
            $this->currentTeam->get(),
            $request->user(),
            $request->validated('email'),
            $request->validated('role'),
        );

        return back()->with('success', "Invitation sent to {$invitation->email}.");
    }

    public function cancelInvitation(TeamInvitation $invitation): RedirectResponse
    {
        $team = $this->currentTeam->get();
        // Invitations aren't team-scoped, so check the team here: 404, not 403,
        // so other teams' invitation ids aren't confirmed to exist.
        abort_unless($invitation->team_id === $team->id, 404);
        $this->authorize('manageMembers', $team);

        $invitation->delete();

        return back()->with('success', 'Invitation cancelled.');
    }

    public function updateMember(Request $request, User $member): RedirectResponse
    {
        $team = $this->currentTeamWithMember($member);
        $this->authorize('changeMember', [$team, $member]);

        $role = $request->validate(['role' => ['required', Rule::in(Roles::assignable())]])['role'];
        $this->teams->changeRole($team, $member, $role);

        return back()->with('success', "{$member->name} is now {$role}.");
    }

    public function removeMember(User $member): RedirectResponse
    {
        $team = $this->currentTeamWithMember($member);
        $this->authorize('changeMember', [$team, $member]);

        $this->teams->removeMember($team, $member);

        return back()->with('success', "{$member->name} was removed from the shop.");
    }

    public function switch(Request $request, Team $team): RedirectResponse
    {
        abort_unless($request->user()->belongsToTeam($team), 404);

        $request->user()->switchTeam($team);

        return redirect()->route('dashboard')->with('success', "Switched to {$team->name}.");
    }

    /**
     * The current team, after making sure $member belongs to it (404 otherwise).
     */
    private function currentTeamWithMember(User $member): Team
    {
        $team = $this->currentTeam->get();
        abort_unless($member->belongsToTeam($team), 404);

        return $team;
    }
}
