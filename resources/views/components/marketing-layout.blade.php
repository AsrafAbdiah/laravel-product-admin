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

    {{-- Page-specific scripts, e.g. the home page hero animation. --}}
    @stack('scripts')
</head>
<body class="min-h-screen bg-canvas font-sans text-ink antialiased">
    <a href="#main" class="sr-only focus:not-sr-only focus:fixed focus:top-2 focus:left-2 focus:z-50 focus:rounded-lg focus:bg-surface focus:px-3 focus:py-2">Skip to content</a>

    <header class="sticky top-0 z-30 border-b border-line bg-canvas/90 backdrop-blur-sm">
        <div class="mx-auto flex h-16 max-w-7xl items-center gap-2 px-4 sm:gap-8 sm:px-6 lg:px-10">
            <a href="{{ route('home') }}" class="shrink-0" aria-label="BandarOS home">
                <x-logo class="h-7" />
            </a>

            <nav class="flex items-center gap-1 font-mono text-xs tracking-wide text-ink-muted uppercase" aria-label="Website">
                <a href="{{ route('home') }}#features" class="hidden px-2 py-2 hover:text-ink md:block">Features</a>
                <a href="{{ route('pricing') }}" @class(['hidden px-2 py-2 hover:text-ink min-[480px]:block', 'text-ink' => request()->routeIs('pricing')])>Pricing</a>
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
        <div class="mx-auto flex max-w-7xl flex-col gap-6 px-4 py-10 sm:flex-row sm:items-center sm:justify-between sm:px-6 lg:px-10">
            <div>
                <a href="{{ route('home') }}" aria-label="BandarOS home">
                    <x-logo class="h-6" />
                </a>
                <p class="mt-3 font-mono text-xs tracking-wide text-ink-subtle uppercase">Inventory for small shops</p>
            </div>

            <nav class="flex flex-wrap gap-x-6 gap-y-2 text-sm text-ink-muted" aria-label="Footer">
                <a href="{{ route('pricing') }}" class="hover:text-ink">Pricing</a>
                <a href="{{ url('/api/documentation') }}" class="hover:text-ink">API docs</a>
                <a href="{{ route('terms') }}" class="hover:text-ink">Terms</a>
                <a href="{{ route('privacy') }}" class="hover:text-ink">Privacy</a>
                <a href="{{ config('bandaros.repository_url') }}" class="hover:text-ink" rel="noopener">Source code</a>
            </nav>
        </div>
        <p class="mx-auto max-w-7xl px-4 pb-8 font-mono text-xs text-ink-subtle sm:px-6 lg:px-10">© {{ date('Y') }} {{ config('app.name') }}</p>
    </footer>

    @include('partials.overlays')
</body>
</html>
