@props(['title', 'description' => null, 'back' => null, 'backLabel' => 'Back'])

<div class="mb-6 space-y-2">
    @if ($back)
        <a href="{{ $back }}" class="inline-flex items-center gap-1 text-sm text-ink-muted hover:text-ink">
            <x-ui.icon name="chevron-left" class="h-4 w-4" /> {{ $backLabel }}
        </a>
    @endif
    <div class="flex flex-wrap items-end justify-between gap-3">
        <div class="min-w-0">
            <h1 class="text-2xl font-semibold tracking-tight text-balance text-ink">{{ $title }}</h1>
            @if ($description)
                <p class="mt-1 text-sm text-ink-muted">{{ $description }}</p>
            @endif
        </div>
        @isset($actions)
            <div class="flex flex-wrap items-center gap-2">{{ $actions }}</div>
        @endisset
    </div>
</div>
