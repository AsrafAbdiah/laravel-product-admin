@props([
    'title' => null,
    'description' => 'BandarOS is inventory software for small shops: products, stock, team roles and an audit log in one place. Start free.',
])

{{-- Layout for the public website (home, pricing, legal pages). --}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    @include('partials.head', ['title' => $title])

    <meta name="description" content="{{ $description }}">
    <link rel="canonical" href="{{ url()->current() }}">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="{{ config('app.name') }}">
    <meta property="og:title" content="{{ $title ?? config('app.name').': inventory for small shops' }}">
    <meta property="og:description" content="{{ $description }}">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:image" content="{{ asset('images/dashboard.jpg') }}">
    <meta name="twitter:card" content="summary_large_image">
</head>
<body class="min-h-screen bg-canvas font-sans text-ink antialiased">
    <a href="#main" class="sr-only focus:not-sr-only focus:fixed focus:top-2 focus:left-2 focus:z-50 focus:rounded-lg focus:bg-surface focus:px-3 focus:py-2">Skip to content</a>

    <header class="sticky top-0 z-30 border-b border-line bg-canvas/85 backdrop-blur">
        <div class="mx-auto flex h-16 max-w-6xl items-center gap-2 px-4 sm:gap-6 sm:px-6">
            <a href="{{ route('home') }}" class="flex shrink-0 items-center gap-2.5" aria-label="BandarOS home">
                <span class="grid h-8 w-8 place-items-center rounded-lg bg-brand font-bold text-on-brand">B</span>
                <span class="hidden text-lg font-semibold tracking-tight min-[400px]:inline">Bandar<span class="text-brand">OS</span></span>
            </a>

            <nav class="flex items-center gap-1 text-sm font-medium text-ink-muted" aria-label="Website">
                <a href="{{ route('home') }}#features" class="hidden rounded-lg px-3 py-2 hover:text-ink sm:block">Features</a>
                <a href="{{ route('pricing') }}" @class(['rounded-lg px-2 py-2 hover:text-ink sm:px-3', 'text-ink' => request()->routeIs('pricing')])>Pricing</a>
            </nav>

            <div class="ml-auto flex items-center gap-1 sm:gap-2">
                <div x-data="themeToggle">
                    <button type="button" @click="toggle()" class="rounded-lg p-2 text-ink-muted hover:bg-surface-muted hover:text-ink"
                            :aria-label="dark ? 'Switch to light mode' : 'Switch to dark mode'" aria-label="Switch theme">
                        <span x-show="!dark"><x-ui.icon name="moon" class="h-5 w-5" /></span>
                        <span x-show="dark" x-cloak><x-ui.icon name="sun" class="h-5 w-5" /></span>
                    </button>
                </div>

                @auth
                    <x-ui.button :href="route('dashboard')">Go to dashboard</x-ui.button>
                @else
                    <a href="{{ route('login') }}" class="rounded-lg px-2 py-2 text-sm font-medium whitespace-nowrap text-ink-muted hover:text-ink sm:px-3">Log in</a>
                    <x-ui.button :href="route('register')">Start free</x-ui.button>
                @endauth
            </div>
        </div>
    </header>

    <main id="main">
        {{ $slot }}
    </main>

    <footer class="border-t border-line">
        <div class="mx-auto flex max-w-6xl flex-col gap-6 px-4 py-10 sm:flex-row sm:items-center sm:justify-between sm:px-6">
            <div>
                <a href="{{ route('home') }}" class="flex items-center gap-2">
                    <span class="grid h-7 w-7 place-items-center rounded-md bg-brand text-sm font-bold text-on-brand">B</span>
                    <span class="font-semibold tracking-tight">Bandar<span class="text-brand">OS</span></span>
                </a>
                <p class="mt-2 text-sm text-ink-subtle">Inventory for small shops.</p>
            </div>

            <nav class="flex flex-wrap gap-x-6 gap-y-2 text-sm text-ink-muted" aria-label="Footer">
                <a href="{{ route('pricing') }}" class="hover:text-ink">Pricing</a>
                <a href="{{ url('/api/documentation') }}" class="hover:text-ink">API docs</a>
                <a href="{{ route('terms') }}" class="hover:text-ink">Terms</a>
                <a href="{{ route('privacy') }}" class="hover:text-ink">Privacy</a>
                <a href="{{ config('bandaros.repository_url') }}" class="hover:text-ink" rel="noopener">Source code</a>
            </nav>
        </div>
        <p class="mx-auto max-w-6xl px-4 pb-8 text-xs text-ink-subtle sm:px-6">© {{ date('Y') }} {{ config('app.name') }}</p>
    </footer>

    @include('partials.overlays')
</body>
</html>
