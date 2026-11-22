@php
    // How to show a logged value: money, yes/no, or plain text.
    $show = function (string $field, mixed $value): string {
        return match (true) {
            $value === null || $value === '' => '—',
            $field === 'price' => 'RM '.number_format((float) $value, 2),
            $field === 'enabled' => $value ? 'enabled' : 'disabled',
            $field === 'image_path' => 'image',
            default => \Illuminate\Support\Str::limit((string) $value, 60),
        };
    };
    $labels = ['category.name' => 'category', 'image_path' => 'image'];
@endphp

<x-layout title="Activity">
    <x-ui.page-header title="Activity" description="Everything that happened in this shop, and who did it." />

    <nav class="mb-4 flex flex-wrap gap-2" aria-label="Filter by area">
        <a href="{{ route('activity.index') }}" @class(['rounded-full px-3 py-1 text-sm', 'bg-brand text-on-brand' => ! $area, 'bg-surface-muted text-ink-muted hover:text-ink' => $area])>All</a>
        @foreach ($areas as $key => $label)
            <a href="{{ route('activity.index', ['area' => $key]) }}" @class(['rounded-full px-3 py-1 text-sm', 'bg-brand text-on-brand' => $area === $key, 'bg-surface-muted text-ink-muted hover:text-ink' => $area !== $key])>{{ $label }}</a>
        @endforeach
    </nav>

    <x-ui.card :padding="false">
        @if ($activities->isEmpty())
            <x-ui.empty-state icon="history" title="Nothing here yet." description="Changes to products, categories and the team will appear here." />
        @else
            <ol class="divide-y divide-line">
                @foreach ($activities as $activity)
                    <li class="flex gap-3 px-5 py-3.5">
                        <span class="mt-0.5 grid h-8 w-8 shrink-0 place-items-center rounded-full bg-surface-muted text-xs font-semibold text-ink-muted">
                            {{ mb_strtoupper(mb_substr($activity->causer?->name ?? 'S', 0, 1)) }}
                        </span>
                        <div class="min-w-0 flex-1">
                            <p class="text-sm">
                                <span class="font-medium">{{ $activity->causer?->name ?? 'System' }}</span>
                                {{ $activity->description }}
                            </p>

                            @if ($activity->event === 'updated' && $changes = $activity->fieldChanges())
                                <ul class="mt-1 space-y-0.5 text-xs text-ink-muted">
                                    @foreach ($changes as $field => [$old, $new])
                                        <li>
                                            <span class="text-ink">{{ $labels[$field] ?? $field }}</span>:
                                            {{ $show($field, $old) }} &rarr; {{ $show($field, $new) }}
                                        </li>
                                    @endforeach
                                </ul>
                            @endif

                            <p class="mt-1 text-xs text-ink-subtle" title="{{ $activity->created_at->format('d M Y, H:i') }}">
                                {{ $activity->created_at->diffForHumans() }} · {{ $areas[$activity->log_name] ?? ucfirst($activity->log_name) }}
                            </p>
                        </div>
                    </li>
                @endforeach
            </ol>
            @if ($activities->hasPages())
                <div class="border-t border-line px-5 py-3">{{ $activities->links() }}</div>
            @endif
        @endif
    </x-ui.card>
</x-layout>
