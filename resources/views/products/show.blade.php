<x-layout :title="$product->name">
    <div class="mx-auto max-w-2xl">
        <a href="{{ route('products.index') }}" class="text-sm text-slate-500 hover:text-slate-800">&larr; Back to products</a>

        <div class="mt-2 mb-6 flex flex-wrap items-start justify-between gap-3">
            <div class="flex min-w-0 items-center gap-4">
                <x-product-image :product="$product" size="h-24 w-24" />
                <div class="min-w-0">
                    <h1 class="text-2xl font-semibold text-slate-900">{{ $product->name }}</h1>
                    <p class="text-sm text-slate-500">{{ $product->category?->name }}</p>
                </div>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('products.edit', $product) }}" class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">Edit</a>
                <form method="POST" action="{{ route('products.destroy', $product) }}" onsubmit="return confirm('Move this product to the trash?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="rounded-md border border-red-300 px-4 py-2 text-sm font-medium text-red-700 hover:bg-red-50">Delete</button>
                </form>
            </div>
        </div>

        <dl class="divide-y divide-slate-100 rounded-lg border border-slate-200 bg-white text-sm">
            <div class="grid grid-cols-3 gap-4 px-6 py-4">
                <dt class="font-medium text-slate-500">Status</dt>
                <dd class="col-span-2"><x-status-badge :enabled="$product->enabled" /></dd>
            </div>
            <div class="grid grid-cols-3 gap-4 px-6 py-4">
                <dt class="font-medium text-slate-500">Price</dt>
                <dd class="col-span-2 tabular-nums">RM {{ number_format($product->price, 2) }}</dd>
            </div>
            <div class="grid grid-cols-3 gap-4 px-6 py-4">
                <dt class="font-medium text-slate-500">Stock</dt>
                <dd class="col-span-2 tabular-nums">{{ $product->stock }}</dd>
            </div>
            <div class="grid grid-cols-3 gap-4 px-6 py-4">
                <dt class="font-medium text-slate-500">Category</dt>
                <dd class="col-span-2">{{ $product->category?->name }} <span class="text-slate-400">(ID {{ $product->category_id }})</span></dd>
            </div>
            <div class="grid grid-cols-3 gap-4 px-6 py-4">
                <dt class="font-medium text-slate-500">Description</dt>
                <dd class="col-span-2 whitespace-pre-line">{{ $product->description ?: '—' }}</dd>
            </div>
            <div class="grid grid-cols-3 gap-4 px-6 py-4">
                <dt class="font-medium text-slate-500">Created</dt>
                <dd class="col-span-2">{{ $product->created_at?->format('d M Y, H:i') }}</dd>
            </div>
            <div class="grid grid-cols-3 gap-4 px-6 py-4">
                <dt class="font-medium text-slate-500">Last updated</dt>
                <dd class="col-span-2">{{ $product->updated_at?->format('d M Y, H:i') }}</dd>
            </div>
        </dl>

        <section class="mt-8" aria-labelledby="history-heading">
            <h2 id="history-heading" class="mb-3 text-lg font-semibold text-slate-900">Stock history</h2>

            <div class="overflow-hidden rounded-lg border border-slate-200 bg-white">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50 text-left text-xs font-semibold tracking-wide text-slate-500 uppercase">
                            <tr>
                                <th class="px-4 py-3">When</th>
                                <th class="px-4 py-3">By</th>
                                <th class="px-4 py-3">Reason</th>
                                <th class="px-4 py-3 text-right">Stock</th>
                                <th class="px-4 py-3 text-right">Change</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($movements as $movement)
                                <tr>
                                    <td class="px-4 py-3 whitespace-nowrap text-slate-600" title="{{ $movement->created_at->format('d M Y, H:i') }}">
                                        {{ $movement->created_at->diffForHumans() }}
                                    </td>
                                    <td class="px-4 py-3">{{ $movement->user?->name ?? 'System' }}</td>
                                    <td class="px-4 py-3 text-slate-600">{{ $movement->reasonLabel() }}</td>
                                    <td class="px-4 py-3 text-right whitespace-nowrap tabular-nums">{{ $movement->old_stock }} &rarr; {{ $movement->new_stock }}</td>
                                    <td class="px-4 py-3 text-right font-medium tabular-nums {{ $movement->change > 0 ? 'text-green-700' : ($movement->change < 0 ? 'text-red-700' : 'text-slate-500') }}">
                                        {{ $movement->change > 0 ? '+' : '' }}{{ $movement->change }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-4 py-8 text-center text-slate-500">No stock changes recorded yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="mt-3">
                {{ $movements->links() }}
            </div>
        </section>
    </div>
</x-layout>
