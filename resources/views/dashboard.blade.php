<x-layout title="Dashboard">
    <div class="mb-6 flex flex-wrap items-end justify-between gap-3">
        <div>
            <h1 class="text-2xl font-semibold text-slate-900">Dashboard</h1>
            <p class="text-sm text-slate-500">Overview of your catalogue and stock.</p>
        </div>
        <a href="{{ route('products.create') }}" class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">+ New Product</a>
    </div>

    {{-- Headline numbers --}}
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
        <a href="{{ route('products.index') }}" class="rounded-lg border border-slate-200 bg-white p-4 hover:border-indigo-300">
            <p class="text-xs font-semibold tracking-wide text-slate-500 uppercase">Products</p>
            <p class="mt-1 text-3xl font-semibold text-slate-900 tabular-nums">{{ number_format($totals['products']) }}</p>
            <p class="mt-1 text-xs text-slate-500">
                {{ number_format($totals['enabled']) }} enabled · {{ number_format($totals['disabled']) }} disabled
            </p>
        </a>

        <a href="{{ route('products.index', ['low_stock' => 1, 'sort' => 'stock']) }}"
           class="rounded-lg border p-4 {{ $totals['low_stock'] ? 'border-amber-300 bg-amber-50 hover:border-amber-400' : 'border-slate-200 bg-white hover:border-indigo-300' }}">
            <p class="text-xs font-semibold tracking-wide uppercase {{ $totals['low_stock'] ? 'text-amber-800' : 'text-slate-500' }}">Low stock</p>
            <p class="mt-1 text-3xl font-semibold tabular-nums {{ $totals['low_stock'] ? 'text-amber-900' : 'text-slate-900' }}">{{ number_format($totals['low_stock']) }}</p>
            <p class="mt-1 text-xs {{ $totals['low_stock'] ? 'text-amber-800' : 'text-slate-500' }}">{{ \App\Models\Product::LOW_STOCK_THRESHOLD }} or fewer left</p>
        </a>

        <div class="rounded-lg border border-slate-200 bg-white p-4 sm:col-span-2 lg:col-span-2">
            <p class="text-xs font-semibold tracking-wide text-slate-500 uppercase">Inventory value</p>
            <p class="mt-1 text-3xl font-semibold text-slate-900 tabular-nums">
                <span class="text-lg font-medium text-slate-500">RM</span> {{ number_format((float) $totals['inventory_value'], 2) }}
            </p>
            <p class="mt-1 text-xs text-slate-500">Price × stock, across all active products</p>
        </div>

        <a href="{{ route('products.trash') }}" class="rounded-lg border border-slate-200 bg-white p-4 hover:border-indigo-300">
            <p class="text-xs font-semibold tracking-wide text-slate-500 uppercase">In trash</p>
            <p class="mt-1 text-3xl font-semibold text-slate-900 tabular-nums">{{ number_format($totals['in_trash']) }}</p>
            <p class="mt-1 text-xs text-slate-500">Can be restored</p>
        </a>
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-2">
        {{-- Products per category, as simple bars scaled to the largest category --}}
        <section class="rounded-lg border border-slate-200 bg-white p-5" aria-labelledby="categories-heading">
            <div class="mb-4 flex items-center justify-between gap-3">
                <h2 id="categories-heading" class="font-semibold text-slate-900">Products per category</h2>
                <a href="{{ route('categories.index') }}" class="text-sm text-indigo-600 hover:text-indigo-800">Manage</a>
            </div>
            @php $max = max(1, $categories->max('products_count') ?? 0); @endphp
            <ul class="space-y-3">
                @forelse ($categories as $category)
                    <li>
                        <a href="{{ route('products.index', ['category_id' => $category->id]) }}" class="group block">
                            <div class="mb-1 flex justify-between gap-3 text-sm">
                                <span class="min-w-0 truncate text-slate-700 group-hover:text-indigo-600">{{ $category->name }}</span>
                                <span class="font-medium text-slate-900 tabular-nums">{{ $category->products_count }}</span>
                            </div>
                            <div class="h-2 rounded-full bg-slate-100" aria-hidden="true">
                                <div class="h-2 rounded-full bg-indigo-500" style="width: {{ round($category->products_count / $max * 100) }}%"></div>
                            </div>
                        </a>
                    </li>
                @empty
                    <li class="text-sm text-slate-500">No categories yet.</li>
                @endforelse
            </ul>
        </section>

        {{-- Products running out --}}
        <section class="rounded-lg border border-slate-200 bg-white p-5" aria-labelledby="low-stock-heading">
            <div class="mb-4 flex items-center justify-between gap-3">
                <h2 id="low-stock-heading" class="font-semibold text-slate-900">Running low</h2>
                <a href="{{ route('products.index', ['low_stock' => 1, 'sort' => 'stock']) }}" class="text-sm text-indigo-600 hover:text-indigo-800">See all</a>
            </div>
            <ul class="divide-y divide-slate-100">
                @forelse ($lowStock as $product)
                    <li>
                        <a href="{{ route('products.show', $product) }}" class="flex items-center gap-3 py-2 hover:text-indigo-600">
                            <x-product-image :product="$product" size="h-9 w-9" />
                            <span class="min-w-0 flex-1">
                                <span class="block truncate text-sm font-medium">{{ $product->name }}</span>
                                <span class="block truncate text-xs text-slate-500">{{ $product->category?->name }}</span>
                            </span>
                            <span class="shrink-0 rounded-full px-2 py-0.5 text-xs font-medium tabular-nums {{ $product->stock === 0 ? 'bg-red-100 text-red-800' : 'bg-amber-100 text-amber-800' }}">
                                {{ $product->stock === 0 ? 'Out of stock' : $product->stock.' left' }}
                            </span>
                        </a>
                    </li>
                @empty
                    <li class="py-2 text-sm text-slate-500">Nothing is running low.</li>
                @endforelse
            </ul>
        </section>
    </div>

    {{-- Latest stock changes across all products --}}
    <section class="mt-6 overflow-hidden rounded-lg border border-slate-200 bg-white" aria-labelledby="movements-heading">
        <h2 id="movements-heading" class="px-5 pt-5 pb-3 font-semibold text-slate-900">Recent stock changes</h2>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-left text-xs font-semibold tracking-wide text-slate-500 uppercase">
                    <tr>
                        <th class="px-5 py-3">When</th>
                        <th class="px-5 py-3">Product</th>
                        <th class="px-5 py-3">By</th>
                        <th class="px-5 py-3">Reason</th>
                        <th class="px-5 py-3 text-right">Change</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($movements as $movement)
                        <tr>
                            <td class="px-5 py-3 whitespace-nowrap text-slate-600" title="{{ $movement->created_at->format('d M Y, H:i') }}">{{ $movement->created_at->diffForHumans() }}</td>
                            <td class="px-5 py-3">
                                @if ($movement->product && ! $movement->product->trashed())
                                    <a href="{{ route('products.show', $movement->product) }}" class="font-medium text-slate-900 hover:text-indigo-600">{{ $movement->product->name }}</a>
                                @else
                                    <span class="text-slate-500">{{ $movement->product?->name ?? 'Deleted product' }} (in trash)</span>
                                @endif
                            </td>
                            <td class="px-5 py-3">{{ $movement->user?->name ?? 'System' }}</td>
                            <td class="px-5 py-3 text-slate-600">{{ $movement->reasonLabel() }}</td>
                            <td class="px-5 py-3 text-right whitespace-nowrap tabular-nums">
                                <span class="text-slate-500">{{ $movement->old_stock }} &rarr; {{ $movement->new_stock }}</span>
                                <span class="ml-2 font-medium {{ $movement->change > 0 ? 'text-green-700' : ($movement->change < 0 ? 'text-red-700' : 'text-slate-500') }}">
                                    {{ $movement->change > 0 ? '+' : '' }}{{ $movement->change }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-5 py-8 text-center text-slate-500">No stock changes yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</x-layout>
