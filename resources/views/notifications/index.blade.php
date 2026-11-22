<x-layout title="Notifications">
    <x-ui.page-header title="Notifications" description="Stock alerts and finished imports and exports.">
        @if (auth()->user()->unreadNotifications()->exists())
            <x-slot:actions>
                <form method="POST" action="{{ route('notifications.read-all') }}">
                    @csrf
                    <x-ui.button type="submit" variant="secondary" icon="check">Mark all as read</x-ui.button>
                </form>
            </x-slot:actions>
        @endif
    </x-ui.page-header>

    <x-ui.card :padding="false">
        @if ($notifications->isEmpty())
            <x-ui.empty-state icon="bell" title="You're all caught up." description="Low stock alerts and finished imports and exports will show up here." />
        @else
            <ul class="divide-y divide-line">
                @foreach ($notifications as $notification)
                    @php
                        $icon = match ($notification->data['type'] ?? null) {
                            'low_stock' => 'alert',
                            'export_ready' => 'download',
                            'import_finished' => 'upload',
                            default => 'bell',
                        };
                    @endphp
                    <li>
                        <a href="{{ route('notifications.show', $notification->id) }}"
                           @class(['flex gap-3 px-5 py-3.5 hover:bg-surface-muted/60', 'bg-brand-soft/30' => $notification->unread()])>
                            <span class="mt-0.5 grid h-8 w-8 shrink-0 place-items-center rounded-full bg-surface-muted text-ink-muted">
                                <x-ui.icon :name="$icon" class="h-4 w-4" />
                            </span>
                            <span class="min-w-0 flex-1">
                                <span class="block text-sm {{ $notification->unread() ? 'font-semibold' : 'font-medium' }}">{{ $notification->data['title'] ?? 'Notification' }}</span>
                                @if (! empty($notification->data['body']))
                                    <span class="block text-sm text-ink-muted">{{ $notification->data['body'] }}</span>
                                @endif
                                <span class="mt-0.5 block text-xs text-ink-subtle">{{ $notification->created_at->diffForHumans() }}</span>
                            </span>
                            @if ($notification->unread())
                                <span class="mt-2 h-2 w-2 shrink-0 rounded-full bg-brand" aria-label="Unread"></span>
                            @endif
                        </a>
                    </li>
                @endforeach
            </ul>
            @if ($notifications->hasPages())
                <div class="border-t border-line px-5 py-3">{{ $notifications->links() }}</div>
            @endif
        @endif
    </x-ui.card>
</x-layout>
