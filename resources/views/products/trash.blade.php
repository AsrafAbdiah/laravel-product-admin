<x-layout title="Trash">
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-2xl font-semibold text-slate-900">Trash</h1>
            <p class="text-sm text-slate-500">{{ $products->total() }} deleted product(s). Restore them, or delete them forever.</p>
        </div>
        <a href="{{ route('products.index') }}" class="rounded-md border border-slate-300 bg-white px-4 py-2 text-sm font-medium hover:bg-slate-50">
            Back to products
        </a>
    </div>

    <form method="GET" action="{{ route('products.trash') }}" class="mb-4 flex flex-wrap gap-2 rounded-lg border border-slate-200 bg-white p-4">
        <input type="search" name="search" value="{{ $filters['search'] }}" placeholder="Search deleted products..." aria-label="Search deleted products"
               class="min-w-0 flex-1 rounded-md border border-slate-300 px-3 py-2 text-sm">
        <button type="submit" class="rounded-md bg-slate-800 px-4 py-2 text-sm font-medium text-white hover:bg-slate-900">Search</button>
        @if ($filters['search'])
            <a href="{{ route('products.trash') }}" class="rounded-md border border-slate-300 px-3 py-2 text-sm hover:bg-slate-50">Reset</a>
        @endif
    </form>

    <div class="overflow-hidden rounded-lg border border-slate-200 bg-white">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-left text-xs font-semibold tracking-wide text-slate-500 uppercase">
                    <tr>
                        <th class="px-4 py-3">Name</th>
                        <th class="px-4 py-3">Category</th>
                        <th class="px-4 py-3 text-right">Price (RM)</th>
                        <th class="px-4 py-3 text-right">Stock</th>
                        <th class="px-4 py-3">Deleted</th>
                        <th class="px-4 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($products as $product)
                        <tr class="hover:bg-slate-50">
                            <td class="px-4 py-3 font-medium text-slate-900">{{ $product->name }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ $product->category?->name }}</td>
                            <td class="px-4 py-3 text-right tabular-nums">{{ number_format($product->price, 2) }}</td>
                            <td class="px-4 py-3 text-right tabular-nums">{{ $product->stock }}</td>
                            <td class="px-4 py-3 text-slate-600" title="{{ $product->deleted_at->format('d M Y, H:i') }}">
                                {{ $product->deleted_at->diffForHumans() }}
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex justify-end gap-3">
                                    <form method="POST" action="{{ route('products.restore', $product) }}">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="text-indigo-600 hover:text-indigo-800">Restore</button>
                                    </form>
                                    <form method="POST" action="{{ route('products.force-destroy', $product) }}"
                                          onsubmit="return confirm('Permanently delete this product? This cannot be undone.')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:text-red-800">Delete forever</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-10 text-center text-slate-500">The trash is empty.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-4">
        {{ $products->links() }}
    </div>
</x-layout>
