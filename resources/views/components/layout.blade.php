@props(['title' => null])

@php
    $team = app(\App\Support\CurrentTeam::class)->get();

    $user = auth()->user();

    // label => [route, icon, is this the current section?, optional badge]
    // Items the user's role can't use are left out.
    $navigation = array_filter([
        'Dashboard' => [route('dashboard'), 'home', request()->routeIs('dashboard'), null],
        'Products' => [route('products.index'), 'box', request()->routeIs('products.*') && ! request()->routeIs('products.trash', 'products.import*'), null],
        'Categories' => [route('categories.index'), 'tag', request()->routeIs('categories.*'), null],
        'Team' => [route('team.show'), 'users', request()->routeIs('team.*'), null],
        'Activity' => $team && $user->can('viewActivity', $team)
            ? [route('activity.index'), 'history', request()->routeIs('activity.*'), null] : null,
        'Notifications' => [route('notifications.index'), 'bell', request()->routeIs('notifications.*'), $unreadCount ?? 0],
        'Import' => $user->can('import', App\Models\Product::class)
            ? [route('products.import'), 'upload', request()->routeIs('products.import*'), null] : null,
        'Trash' => $user->can('viewTrash', App\Models\Product::class)
            ? [route('products.trash'), 'trash', request()->routeIs('products.trash'), $trashCount ?? 0] : null,
    ]);
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    @include('partials.head', ['title' => $title])
</head>
<body class="h-full bg-canvas font-sans text-ink antialiased" x-data="{ sidebarOpen: false }" @keydown.escape.window="sidebarOpen = false">
    <a href="#main" class="sr-only focus:not-sr-only focus:fixed focus:top-2 focus:left-2 focus:z-50 focus:rounded-lg focus:bg-surface focus:px-3 focus:py-2">Skip to content</a>

    {{-- Mobile overlay --}}
    <div x-show="sidebarOpen" x-cloak x-transition.opacity class="fixed inset-0 z-30 bg-black/40 lg:hidden" @click="sidebarOpen = false"></div>

    {{-- Sidebar --}}
    <aside class="fixed inset-y-0 left-0 z-40 flex w-64 -translate-x-full flex-col border-r border-line bg-surface transition-transform lg:translate-x-0"
           :class="sidebarOpen && 'translate-x-0'" aria-label="Main navigation">
        <div class="flex h-16 shrink-0 items-center justify-between px-5">
            <a href="{{ route('dashboard') }}" class="flex items-center gap-2.5">
                <span class="grid h-8 w-8 place-items-center rounded-lg bg-brand font-bold text-on-brand">B</span>
                <span class="text-lg font-semibold tracking-tight">Bandar<span class="text-brand">OS</span></span>
            </a>
            <button type="button" class="rounded-lg p-1.5 text-ink-muted hover:bg-surface-muted lg:hidden" @click="sidebarOpen = false" aria-label="Close menu">
                <x-ui.icon name="x" />
            </button>
        </div>

        @if ($team)
            @php $otherTeams = $user->teams->reject(fn ($t) => $t->is($team)); @endphp
            {{-- Current shop, with a switcher when the user belongs to more than one --}}
            <div class="relative mx-3 mb-3" x-data="{ open: false }" @click.outside="open = false">
                <button type="button" @click="open = !open" @if ($otherTeams->isEmpty()) disabled @endif
                        class="flex w-full items-center gap-2.5 rounded-lg border border-line bg-surface-muted px-3 py-2.5 text-left enabled:hover:border-line-strong"
                        :aria-expanded="open" aria-haspopup="true">
                    <x-ui.icon name="store" class="h-4 w-4 shrink-0 text-ink-muted" />
                    <span class="min-w-0 flex-1">
                        <span class="block text-[11px] font-medium tracking-wide text-ink-subtle uppercase">Shop</span>
                        <span class="block truncate text-sm font-medium">{{ $team->name }}</span>
                    </span>
                    @if ($otherTeams->isNotEmpty())
                        <x-ui.icon name="switch" class="h-4 w-4 shrink-0 text-ink-subtle" />
                    @endif
                </button>

                @if ($otherTeams->isNotEmpty())
                    <div x-show="open" x-cloak x-transition.opacity
                         class="absolute inset-x-0 top-full z-10 mt-1 rounded-lg border border-line bg-surface p-1 shadow-lg">
                        <p class="px-2 py-1 text-[11px] font-medium tracking-wide text-ink-subtle uppercase">Switch to</p>
                        @foreach ($otherTeams as $otherTeam)
                            <form method="POST" action="{{ route('teams.switch', $otherTeam) }}">
                                @csrf
                                <button type="submit" class="w-full truncate rounded-md px-2 py-1.5 text-left text-sm hover:bg-surface-muted">{{ $otherTeam->name }}</button>
                            </form>
                        @endforeach
                    </div>
                @endif
            </div>
        @endif

        <nav class="flex-1 space-y-1 overflow-y-auto px-3">
            @foreach ($navigation as $label => [$url, $icon, $active, $badge])
                <a href="{{ $url }}" @if ($active) aria-current="page" @endif
                   @class([
                       'group flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-colors',
                       'bg-brand-soft text-brand-ink' => $active,
                       'text-ink-muted hover:bg-surface-muted hover:text-ink' => ! $active,
                   ])>
                    <x-ui.icon :name="$icon" :class="'h-5 w-5 '.($active ? 'text-brand' : 'text-ink-subtle group-hover:text-ink-muted')" />
                    <span class="flex-1">{{ $label }}</span>
                    @if ($badge)
                        <span class="rounded-full bg-surface-muted px-2 text-xs font-medium text-ink-muted tabular-nums">{{ $badge }}</span>
                    @endif
                </a>
            @endforeach

            <div class="my-3 border-t border-line"></div>

            <a href="{{ url('/api/documentation') }}" target="_blank" rel="noopener"
               class="group flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium text-ink-muted hover:bg-surface-muted hover:text-ink">
                <x-ui.icon name="code" class="h-5 w-5 text-ink-subtle group-hover:text-ink-muted" />
                API Docs
            </a>
        </nav>

        <div class="space-y-1 border-t border-line p-3">
            <div x-data="themeToggle">
                <button type="button" @click="toggle()"
                        class="flex w-full items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium text-ink-muted hover:bg-surface-muted hover:text-ink">
                    <span x-show="!dark"><x-ui.icon name="moon" class="h-5 w-5" /></span>
                    <span x-show="dark" x-cloak><x-ui.icon name="sun" class="h-5 w-5" /></span>
                    <span x-text="dark ? 'Light mode' : 'Dark mode'">Dark mode</span>
                </button>
            </div>

            <div class="flex items-center gap-1 rounded-lg">
                <a href="{{ route('settings.profile') }}" title="Account settings"
                   class="flex min-w-0 flex-1 items-center gap-3 rounded-lg px-3 py-2 hover:bg-surface-muted {{ request()->routeIs('settings.*') ? 'bg-surface-muted' : '' }}">
                    <span class="grid h-8 w-8 shrink-0 place-items-center rounded-full bg-brand-soft text-sm font-semibold text-brand-ink">
                        {{ mb_strtoupper(mb_substr(auth()->user()->name, 0, 1)) }}
                    </span>
                    <span class="min-w-0 flex-1">
                        <span class="block truncate text-sm font-medium">{{ auth()->user()->name }}</span>
                        <span class="block truncate text-xs text-ink-subtle">Account settings</span>
                    </span>
                </a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="rounded-lg p-1.5 text-ink-subtle hover:bg-surface-muted hover:text-danger" title="Log out" aria-label="Log out">
                        <x-ui.icon name="logout" />
                    </button>
                </form>
            </div>
        </div>
    </aside>

    <div class="flex min-h-full flex-col lg:pl-64">
        {{-- Mobile top bar --}}
        <header class="sticky top-0 z-20 flex h-14 items-center gap-3 border-b border-line bg-surface/90 px-4 backdrop-blur lg:hidden">
            <button type="button" class="-ml-1 rounded-lg p-1.5 text-ink-muted hover:bg-surface-muted" @click="sidebarOpen = true" aria-label="Open menu">
                <x-ui.icon name="menu" />
            </button>
            <span class="font-semibold">Bandar<span class="text-brand">OS</span></span>
            @if ($team)
                <span class="ml-auto truncate text-sm text-ink-muted">{{ $team->name }}</span>
            @endif
        </header>

        <main id="main" class="mx-auto w-full max-w-6xl flex-1 px-4 py-6 sm:px-6 lg:px-8 lg:py-8">
            {{ $slot }}
        </main>
    </div>

    @include('partials.overlays')
</body>
</html>
