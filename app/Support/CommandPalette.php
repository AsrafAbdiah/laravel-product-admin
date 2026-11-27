<?php

namespace App\Support;

use App\Models\Category;
use App\Models\Product;
use App\Models\Team;
use App\Models\User;

/**
 * The pages and actions listed in the Ctrl+K command palette. Built on the
 * server, so it only offers what this user's role allows; the policies
 * still check every page when it is opened.
 */
class CommandPalette
{
    /**
     * @return list<array{label: string, url: string, keywords: string, action?: string}>
     */
    public function commands(User $user, ?Team $team): array
    {
        $commands = [
            $this->command('Dashboard', route('dashboard'), 'home overview stats'),
            $this->command('Products', route('products.index'), 'items stock inventory list'),
            $user->can('create', Product::class) ? $this->command('New product', route('products.create'), 'add create item') : null,
            $user->can('import', Product::class) ? $this->command('Import products', route('products.import'), 'excel csv upload') : null,
            $user->can('viewTrash', Product::class) ? $this->command('Trash', route('products.trash'), 'deleted restore bin') : null,
            $this->command('Categories', route('categories.index'), 'groups'),
            $user->can('create', Category::class) ? $this->command('New category', route('categories.create'), 'add create group') : null,
            $this->command('Team', route('team.show'), 'members roles plan shop'),
            $team && $user->can('update', $team) ? $this->command('Invite a teammate', route('team.show').'#invite', 'add member staff email') : null,
            $team && $user->can('viewActivity', $team) ? $this->command('Activity log', route('activity.index'), 'audit history changes') : null,
            $this->command('Notifications', route('notifications.index'), 'alerts inbox'),
            $this->command('Account settings', route('settings.profile'), 'profile name email password'),
            $this->command('Two-factor login', route('settings.profile').'#two-factor', '2fa security authenticator'),
            $this->command('Active sessions', route('settings.sessions'), 'devices logout security'),
            $this->command('API tokens', route('settings.tokens'), 'api keys integration'),
            $this->command('API documentation', url('/api/documentation'), 'swagger rest docs'),
            $this->command('Toggle dark mode', '#', 'theme light dark appearance', 'theme'),
        ];

        return array_values(array_filter($commands));
    }

    /**
     * @return array{label: string, url: string, keywords: string, action?: string}
     */
    private function command(string $label, string $url, string $keywords, ?string $action = null): array
    {
        return ['label' => $label, 'url' => $url, 'keywords' => $keywords] + ($action ? ['action' => $action] : []);
    }
}
