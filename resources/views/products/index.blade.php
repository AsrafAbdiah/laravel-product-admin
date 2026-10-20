<x-layout title="Products">
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-2xl font-semibold text-slate-900">Products</h1>
            <p class="text-sm text-slate-500">{{ $products->total() }} product(s) found</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('products.export', request()->query()) }}"
               class="rounded-md border border-slate-300 bg-white px-4 py-2 text-sm font-medium hover:bg-slate-50">
                Export to Excel
            </a>
            <a href="{{ route('products.create') }}"
               class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">
                + New Product
            </a>
        </div>
    </div>

    {{-- Filters --}}
    <form method="GET" action="{{ route('products.index') }}" class="mb-4 grid gap-3 rounded-lg border border-slate-200 bg-white p-4 sm:grid-cols-6">
        <input type="search" name="search" value="{{ $filters['search'] }}" placeholder="Search by name..."
               class="rounded-md border border-slate-300 px-3 py-2 text-sm sm:col-span-2">

        <select name="category_id" class="rounded-md border border-slate-300 px-3 py-2 text-sm" aria-label="Category">
            <option value="">All categories</option>
            @foreach ($categories as $category)
                <option value="{{ $category->id }}" @selected($filters['category_id'] === $category->id)>{{ $category->name }}</option>
            @endforeach
        </select>

        <select name="status" class="rounded-md border border-slate-300 px-3 py-2 text-sm" aria-label="Status">
            <option value="">All statuses</option>
            <option value="enabled" @selected($filters['status'] === 'enabled')>Enabled</option>
            <option value="disabled" @selected($filters['status'] === 'disabled')>Disabled</option>
        </select>
        <label class="flex items-center gap-2 text-sm text-slate-600">
            <input type="checkbox" name="low_stock" value="1" class="rounded border-slate-300" @checked($filters['low_stock'])>
            Low stock only
        </label>

        <div class="flex gap-2">
            <button type="submit" class="flex-1 rounded-md bg-slate-800 px-4 py-2 text-sm font-medium text-white hover:bg-slate-900">Filter</button>
            <a href="{{ route('products.index') }}" class="rounded-md border border-slate-300 px-3 py-2 text-sm hover:bg-slate-50">Reset</a>
        </div>
    </form>

    {{-- Table with bulk selection (Alpine.js keeps track of the checked ids) --}}
    <div x-data="{ selected: [], pageIds: @js($products->pluck('id')) }" class="overflow-hidden rounded-lg border border-slate-200 bg-white">
        <form method="POST" action="{{ route('products.bulk-destroy') }}"
              x-show="selected.length" x-cloak
              @submit="if (! confirm(`Delete ${selected.length} selected product(s)?`)) $event.preventDefault()"
              class="flex items-center justify-between border-b border-slate-200 bg-amber-50 px-4 py-2 text-sm">
            @csrf
            @method('DELETE')
            <template x-for="id in selected" :key="id">
                <input type="hidden" name="ids[]" :value="id">
            </template>
            <span><span x-text="selected.length"></span> selected</span>
            <button type="submit" class="rounded-md bg-red-600 px-3 py-1.5 font-medium text-white hover:bg-red-700">Delete selected</button>
        </form>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-left text-xs font-semibold tracking-wide text-slate-500 uppercase">
                    <tr>
                        <th class="w-10 px-4 py-3">
                            <input type="checkbox" aria-label="Select all on this page" class="rounded border-slate-300"
                                   :checked="pageIds.length && selected.length === pageIds.length"
                                   @change="selected = $event.target.checked ? [...pageIds] : []">
                        </th>
                        <th class="px-4 py-3">Name</th>
                        <th class="px-4 py-3">Category</th>
                        <th class="px-4 py-3 text-right">Price (RM)</th>
                        <th class="px-4 py-3 text-right">Stock</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($products as $product)
                        <tr class="hover:bg-slate-50">
                            <td class="px-4 py-3">
                                <input type="checkbox" value="{{ $product->id }}" x-model.number="selected"
                                       aria-label="Select {{ $product->name }}" class="rounded border-slate-300">
                            </td>
                            <td class="px-4 py-3 font-medium text-slate-900">
                                <a href="{{ route('products.show', $product) }}" class="hover:text-indigo-600">{{ $product->name }}</a>
                            </td>
                            <td class="px-4 py-3 text-slate-600">{{ $product->category?->name }}</td>
                            <td class="px-4 py-3 text-right tabular-nums">{{ number_format($product->price, 2) }}</td>
                            <td class="px-4 py-3 text-right tabular-nums">{{ $product->stock }}</td>
                            <td class="px-4 py-3"><x-status-badge :enabled="$product->enabled" /></td>
                            <td class="px-4 py-3">
                                <div class="flex justify-end gap-3">
                                    <a href="{{ route('products.show', $product) }}" class="text-slate-600 hover:text-slate-900">View</a>
                                    <a href="{{ route('products.edit', $product) }}" class="text-indigo-600 hover:text-indigo-800">Edit</a>
                                    <form method="POST" action="{{ route('products.destroy', $product) }}"
                                          onsubmit="return confirm('Delete this product?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:text-red-800">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-10 text-center text-slate-500">
                                No products found. <a href="{{ route('products.create') }}" class="text-indigo-600 hover:underline">Create one</a>.
                            </td>
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
