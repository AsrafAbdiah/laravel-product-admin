@props(['icon' => 'box', 'title', 'description' => null])

<div {{ $attributes->merge(['class' => 'flex flex-col items-center px-6 py-12 text-center']) }}>
    <div class="mb-3 grid h-12 w-12 place-items-center rounded-full bg-surface-muted text-ink-subtle">
        <x-ui.icon :name="$icon" class="h-6 w-6" />
    </div>
    <p class="font-medium text-ink">{{ $title }}</p>
    @if ($description)
        <p class="mt-1 max-w-sm text-sm text-ink-muted">{{ $description }}</p>
    @endif
    @if ($slot->isNotEmpty())
        <div class="mt-4">{{ $slot }}</div>
    @endif
</div>
