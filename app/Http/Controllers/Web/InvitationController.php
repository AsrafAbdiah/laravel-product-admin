<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\TeamInvitation;
use App\Services\TeamService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * The link in an invitation email. Showing the page (GET) never changes
 * anything; joining needs an explicit click (POST), so email scanners or
 * link previews can't accept an invitation by opening it.
 */
class InvitationController extends Controller
{
    public function show(Request $request, string $token): View
    {
        $invitation = $this->findUsable($token);

        return view('invitations.show', [
            'invitation' => $invitation,
            'token' => $token,
            'emailMatches' => $this->emailMatches($request, $invitation),
        ]);
    }

    public function accept(Request $request, string $token, TeamService $teams): RedirectResponse
    {
        $invitation = $this->findUsable($token);

        // Only the person the invitation was sent to can use it.
        abort_unless($this->emailMatches($request, $invitation), 403, 'This invitation was sent to a different email address.');

        $team = $teams->accept($invitation, $request->user());

        return redirect()->route('dashboard')->with('success', "Welcome to {$team->name}!");
    }

    private function findUsable(string $token): TeamInvitation
    {
        $invitation = TeamInvitation::findByToken($token);

        abort_if($invitation === null, 404, 'This invitation does not exist or was already used.');
        abort_if($invitation->isExpired(), 410, 'This invitation has expired. Ask for a new one.');

        return $invitation;
    }

    private function emailMatches(Request $request, TeamInvitation $invitation): bool
    {
        return mb_strtolower($request->user()->email) === $invitation->email;
    }
}
