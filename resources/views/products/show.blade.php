<x-layout :title="$product->name">
    <div class="mx-auto max-w-4xl">
        <x-ui.page-header :title="$product->name" :description="$product->category?->name" :back="route('products.index')" back-label="Products">
            <x-slot:actions>
                <x-ui.button variant="secondary" :href="route('products.edit', $product)" icon="edit">Edit</x-ui.button>
                <form method="POST" action="{{ route('products.destroy', $product) }}"
                      data-confirm="Move &quot;{{ $product->name }}&quot; to the trash? You can restore it later."
                      data-confirm-title="Move to trash" data-confirm-label="Move to trash">
                    @csrf
                    @method('DELETE')
                    <x-ui.button type="submit" variant="secondary" icon="trash" class="hover:text-danger">Delete</x-ui.button>
                </form>
            </x-slot:actions>
        </x-ui.page-header>

        <div class="grid gap-6 md:grid-cols-[16rem_1fr]">
            <x-product-image :product="$product" size="aspect-square w-full max-w-64" class="rounded-xl" />

            <x-ui.card :padding="false">
                <dl class="divide-y divide-line text-sm">
                    @foreach ([
                        'Status' => null,
                        'Price' => 'RM '.number_format($product->price, 2),
                        'Stock' => null,
                        'Category' => null,
                        'Description' => $product->description ?: '—',
                        'Created' => $product->created_at?->format('d M Y, H:i'),
                        'Last updated' => $product->updated_at?->format('d M Y, H:i'),
                    ] as $label => $value)
                        <div class="grid grid-cols-3 gap-4 px-5 py-3.5">
                            <dt class="font-medium text-ink-muted">{{ $label }}</dt>
                            <dd class="col-span-2 min-w-0">
                                @switch($label)
                                    @case('Status') <x-status-badge :enabled="$product->enabled" /> @break
                                    @case('Stock') <x-stock-level :stock="$product->stock" /> @break
                                    @case('Category') {{ $product->category?->name }} <span class="text-ink-subtle">(ID {{ $product->category_id }})</span> @break
                                    @case('Description') <span class="whitespace-pre-line">{{ $value }}</span> @break
                                    @default <span class="tabular-nums">{{ $value }}</span>
                                @endswitch
                            </dd>
                        </div>
                    @endforeach
                </dl>
            </x-ui.card>
        </div>

        <x-ui.card title="Stock history" :padding="false" class="mt-6">
            @if ($movements->isEmpty())
                <x-ui.empty-state icon="box" title="No stock changes recorded yet." />
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-line text-sm">
                        <thead class="bg-surface-muted text-left text-xs font-semibold tracking-wide text-ink-muted uppercase">
                            <tr>
                                <th class="px-5 py-3">When</th>
                                <th class="px-5 py-3">By</th>
                                <th class="px-5 py-3">Reason</th>
                                <th class="px-5 py-3 text-right">Stock</th>
                                <th class="px-5 py-3 text-right">Change</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-line">
                            @foreach ($movements as $movement)
                                <tr>
                                    <td class="px-5 py-3 whitespace-nowrap text-ink-muted" title="{{ $movement->created_at->format('d M Y, H:i') }}">
                                        {{ $movement->created_at->diffForHumans() }}
                                    </td>
                                    <td class="px-5 py-3">{{ $movement->user?->name ?? 'System' }}</td>
                                    <td class="px-5 py-3 text-ink-muted">{{ $movement->reasonLabel() }}</td>
                                    <td class="px-5 py-3 text-right whitespace-nowrap tabular-nums">{{ $movement->old_stock }} &rarr; {{ $movement->new_stock }}</td>
                                    <td class="px-5 py-3 text-right">
                                        <x-ui.badge :tone="$movement->change > 0 ? 'success' : ($movement->change < 0 ? 'danger' : 'neutral')">
                                            {{ $movement->change > 0 ? '+' : '' }}{{ $movement->change }}
                                        </x-ui.badge>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @if ($movements->hasPages())
                    <div class="border-t border-line px-5 py-3">{{ $movements->links() }}</div>
                @endif
            @endif
        </x-ui.card>
    </div>
</x-layout>
