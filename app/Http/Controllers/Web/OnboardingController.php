<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Support\CurrentTeam;
use Illuminate\Http\RedirectResponse;

class OnboardingController extends Controller
{
    /**
     * Hide the "Get your shop set up" checklist for everyone in the shop.
     */
    public function dismiss(CurrentTeam $currentTeam): RedirectResponse
    {
        $team = $currentTeam->get();
        $this->authorize('update', $team);

        $team->forceFill(['onboarding_dismissed_at' => now()])->save();

        return back()->with('success', 'Checklist hidden.');
    }
}
