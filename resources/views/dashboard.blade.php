<x-layout title="Dashboard">
    <x-ui.page-header title="Dashboard" description="Overview of your catalogue and stock.">
        <x-slot:actions>
            <x-ui.button :href="route('products.create')" icon="plus">New product</x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>

    {{-- Headline numbers --}}
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-ui.stat label="Products" :value="number_format($totals['products'])" :href="route('products.index')"
                   :hint="number_format($totals['enabled']).' enabled · '.number_format($totals['disabled']).' disabled'" />

        <x-ui.stat label="Low stock" :value="number_format($totals['low_stock'])" :tone="$totals['low_stock'] ? 'warning' : 'neutral'"
                   :href="route('products.index', ['low_stock' => 1, 'sort' => 'stock'])"
                   :hint="\App\Models\Product::LOW_STOCK_THRESHOLD.' or fewer left'" />

        <x-ui.stat label="Inventory value" :value="'RM '.number_format((float) $totals['inventory_value'], 2)"
                   hint="Price × stock, across all active products" />

        <x-ui.stat label="In trash" :value="number_format($totals['in_trash'])" :href="route('products.trash')" hint="Can be restored" />
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-2">
        {{-- Products per category, as simple bars scaled to the largest category --}}
        <x-ui.card title="Products per category">
            <x-slot:actions>
                <a href="{{ route('categories.index') }}" class="text-sm font-medium text-brand hover:underline">Manage</a>
            </x-slot:actions>

            @php $max = max(1, $categories->max('products_count') ?? 0); @endphp
            @if ($categories->isEmpty())
                <x-ui.empty-state icon="tag" title="No categories yet." class="py-6" />
            @else
                <ul class="space-y-3.5">
                    @foreach ($categories as $category)
                        <li>
                            <a href="{{ route('products.index', ['category_id' => $category->id]) }}" class="group block">
                                <div class="mb-1.5 flex justify-between gap-3 text-sm">
                                    <span class="min-w-0 truncate text-ink-muted group-hover:text-brand">{{ $category->name }}</span>
                                    <span class="font-medium tabular-nums">{{ $category->products_count }}</span>
                                </div>
                                <div class="h-2 rounded-full bg-surface-muted" aria-hidden="true">
                                    <div class="h-2 rounded-full bg-brand" style="width: {{ round($category->products_count / $max * 100) }}%"></div>
                                </div>
                            </a>
                        </li>
                    @endforeach
                </ul>
            @endif
        </x-ui.card>

        {{-- Products running out --}}
        <x-ui.card title="Running low">
            <x-slot:actions>
                <a href="{{ route('products.index', ['low_stock' => 1, 'sort' => 'stock']) }}" class="text-sm font-medium text-brand hover:underline">See all</a>
            </x-slot:actions>

            @if ($lowStock->isEmpty())
                <x-ui.empty-state icon="check" title="Nothing is running low." class="py-6" />
            @else
                <ul class="-my-2 divide-y divide-line">
                    @foreach ($lowStock as $product)
                        <li>
                            <a href="{{ route('products.show', $product) }}" class="flex items-center gap-3 py-2.5 hover:text-brand">
                                <x-product-image :product="$product" size="h-9 w-9" />
                                <span class="min-w-0 flex-1">
                                    <span class="block truncate text-sm font-medium">{{ $product->name }}</span>
                                    <span class="block truncate text-xs text-ink-muted">{{ $product->category?->name }}</span>
                                </span>
                                <x-stock-level :stock="$product->stock" />
                            </a>
                        </li>
                    @endforeach
                </ul>
            @endif
        </x-ui.card>
    </div>

    {{-- Latest stock changes across all products --}}
    <x-ui.card title="Recent stock changes" :padding="false" class="mt-6">
        @if ($movements->isEmpty())
            <x-ui.empty-state icon="box" title="No stock changes yet." />
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-line text-sm">
                    <thead class="bg-surface-muted text-left text-xs font-semibold tracking-wide text-ink-muted uppercase">
                        <tr>
                            <th class="px-5 py-3">When</th>
                            <th class="px-5 py-3">Product</th>
                            <th class="px-5 py-3">By</th>
                            <th class="px-5 py-3">Reason</th>
                            <th class="px-5 py-3 text-right">Change</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-line">
                        @foreach ($movements as $movement)
                            <tr>
                                <td class="px-5 py-3 whitespace-nowrap text-ink-muted" title="{{ $movement->created_at->format('d M Y, H:i') }}">{{ $movement->created_at->diffForHumans() }}</td>
                                <td class="px-5 py-3">
                                    @if ($movement->product && ! $movement->product->trashed())
                                        <a href="{{ route('products.show', $movement->product) }}" class="font-medium hover:text-brand">{{ $movement->product->name }}</a>
                                    @else
                                        <span class="text-ink-muted">{{ $movement->product?->name ?? 'Deleted product' }} (in trash)</span>
                                    @endif
                                </td>
                                <td class="px-5 py-3">{{ $movement->user?->name ?? 'System' }}</td>
                                <td class="px-5 py-3 text-ink-muted">{{ $movement->reasonLabel() }}</td>
                                <td class="px-5 py-3 text-right whitespace-nowrap">
                                    <span class="text-ink-subtle tabular-nums">{{ $movement->old_stock }} &rarr; {{ $movement->new_stock }}</span>
                                    <x-ui.badge class="ml-2" :tone="$movement->change > 0 ? 'success' : ($movement->change < 0 ? 'danger' : 'neutral')">
                                        {{ $movement->change > 0 ? '+' : '' }}{{ $movement->change }}
                                    </x-ui.badge>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-ui.card>
</x-layout>
