<?php

namespace App\Services;

use App\Models\Team;
use App\Models\User;

/**
 * The "Get your shop set up" checklist on the dashboard. Each step is worked
 * out from the shop's real data (has it got a category yet? a teammate?),
 * so it ticks itself off as the shop is set up; nothing extra is stored.
 */
class OnboardingChecklist
{
    /**
     * The checklist to show this user, or null when it shouldn't be shown:
     * the user can't manage the shop, a manager hid it, or every step is done.
     *
     * @return array{steps: list<array{title: string, description: string, url: string, action: string, done: bool}>, done: int, total: int}|null
     */
    public function forDashboard(Team $team, User $user): ?array
    {
        // Cheap checks first, so a shop that hid the checklist costs no extra queries.
        if ($team->onboarding_dismissed_at !== null || $user->cannot('update', $team)) {
            return null;
        }

        $steps = $this->steps($team, $user);
        $done = count(array_filter($steps, fn (array $step) => $step['done']));

        if ($done === count($steps)) {
            return null;
        }

        return ['steps' => $steps, 'done' => $done, 'total' => count($steps)];
    }

    /**
     * Each step is a single EXISTS query (or a column on the user), so the
     * whole checklist is a handful of tiny queries.
     *
     * @return list<array{title: string, description: string, url: string, action: string, done: bool}>
     */
    public function steps(Team $team, User $user): array
    {
        return [
            [
                'title' => 'Create a category',
                'description' => 'Group your products, e.g. Drinks or Snacks.',
                'url' => route('categories.create'),
                'action' => 'Add category',
                'done' => $team->categories()->exists(),
            ],
            [
                'title' => 'Add your first product',
                'description' => 'Or import your existing list from Excel.',
                'url' => route('products.create'),
                'action' => 'Add product',
                'done' => $team->products()->exists(),
            ],
            [
                'title' => 'Add a product photo',
                'description' => 'Photos make products easy to spot in the list.',
                'url' => route('products.index'),
                'action' => 'Open products',
                'done' => $team->products()->whereNotNull('image_path')->exists(),
            ],
            [
                'title' => 'Invite a teammate',
                'description' => 'Give them a role, so they see only what they need.',
                'url' => route('team.show').'#invite',
                'action' => 'Invite',
                // Someone besides you has joined, or an invitation is waiting.
                'done' => $team->invitations()->exists() || $team->users()->whereKeyNot($user->id)->exists(),
            ],
            [
                'title' => 'Turn on two-factor login',
                'description' => 'Protect your account with a code from your phone.',
                'url' => route('settings.profile').'#two-factor',
                'action' => 'Set up',
                'done' => $user->hasEnabledTwoFactorAuthentication(),
            ],
        ];
    }
}
