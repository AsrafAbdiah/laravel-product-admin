{{-- Tabs shared by the account settings pages. --}}
<nav class="mb-6 flex gap-1 border-b border-line" aria-label="Settings sections">
    @foreach (['settings.profile' => 'Profile & security', 'settings.tokens' => 'API tokens'] as $routeName => $label)
        <a href="{{ route($routeName) }}" @if (request()->routeIs($routeName)) aria-current="page" @endif
           @class([
               '-mb-px border-b-2 px-3 py-2 text-sm font-medium',
               'border-brand text-brand' => request()->routeIs($routeName),
               'border-transparent text-ink-muted hover:text-ink' => ! request()->routeIs($routeName),
           ])>{{ $label }}</a>
    @endforeach
</nav>
