<x-layout title="Products">
    <x-ui.page-header title="Products" :description="$products->total().' product(s) found'">
        <x-slot:actions>
            @can('export', App\Models\Product::class)
                <x-ui.button variant="secondary" :href="route('products.export', request()->query())" icon="download">Export</x-ui.button>
            @endcan
            @can('create', App\Models\Product::class)
                <x-ui.button :href="route('products.create')" icon="plus">New product</x-ui.button>
            @endcan
        </x-slot:actions>
    </x-ui.page-header>

    {{-- Filters --}}
    <form method="GET" action="{{ route('products.index') }}" class="mb-4 rounded-xl border border-line bg-surface p-4 shadow-xs">
        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-6">
            <div class="relative sm:col-span-2">
                <x-ui.icon name="search" class="pointer-events-none absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-ink-subtle" />
                <x-ui.input name="search" type="search" :value="$filters['search']" placeholder="Search by name..." aria-label="Search by name" class="pl-9" />
            </div>

            <x-ui.select name="category_id" aria-label="Category">
                <option value="">All categories</option>
                @foreach ($categories as $category)
                    <option value="{{ $category->id }}" @selected($filters['category_id'] === $category->id)>{{ $category->name }}</option>
                @endforeach
            </x-ui.select>

            <x-ui.select name="status" aria-label="Status">
                <option value="">All statuses</option>
                <option value="enabled" @selected($filters['status'] === 'enabled')>Enabled</option>
                <option value="disabled" @selected($filters['status'] === 'disabled')>Disabled</option>
            </x-ui.select>

            <x-ui.select name="sort" aria-label="Sort by">
                <option value="">Newest first</option>
                <option value="created_at" @selected($filters['sort'] === 'created_at')>Oldest first</option>
                <option value="name" @selected($filters['sort'] === 'name')>Name A–Z</option>
                <option value="-name" @selected($filters['sort'] === '-name')>Name Z–A</option>
                <option value="price" @selected($filters['sort'] === 'price')>Price: low to high</option>
                <option value="-price" @selected($filters['sort'] === '-price')>Price: high to low</option>
                <option value="stock" @selected($filters['sort'] === 'stock')>Stock: low to high</option>
                <option value="-stock" @selected($filters['sort'] === '-stock')>Stock: high to low</option>
            </x-ui.select>

            <div class="flex items-center justify-between gap-2">
                <label class="flex items-center gap-2 text-sm text-ink-muted">
                    <input type="checkbox" name="low_stock" value="1" class="h-4 w-4 rounded border-line-strong accent-brand" @checked($filters['low_stock'])>
                    Low stock
                </label>
            </div>
        </div>

        <div class="mt-3 flex justify-end gap-2">
            <x-ui.button variant="ghost" :href="route('products.index')">Reset</x-ui.button>
            <x-ui.button type="submit" variant="secondary">Apply filters</x-ui.button>
        </div>
    </form>

    {{-- Results, with bulk selection (Alpine.js keeps track of the checked ids) --}}
    @php $canBulkDelete = auth()->user()->can('deleteAny', App\Models\Product::class); @endphp
    <div x-data="{ selected: [], pageIds: @js($products->pluck('id')) }" class="overflow-hidden rounded-xl border border-line bg-surface shadow-xs">
        @if ($canBulkDelete)
        <form method="POST" action="{{ route('products.bulk-destroy') }}"
              x-show="selected.length" x-cloak
              :data-confirm="`Move ${selected.length} selected product(s) to the trash? You can restore them later.`"
              data-confirm-title="Move to trash" data-confirm-label="Move to trash"
              class="flex items-center justify-between gap-3 border-b border-line bg-brand-soft px-4 py-2.5 text-sm">
            @csrf
            @method('DELETE')
            <template x-for="id in selected" :key="id">
                <input type="hidden" name="ids[]" :value="id">
            </template>
            <span class="font-medium text-brand-ink"><span x-text="selected.length"></span> selected</span>
            <div class="flex gap-2">
                <x-ui.button variant="ghost" size="sm" @click="selected = []">Clear</x-ui.button>
                <x-ui.button type="submit" variant="danger" size="sm" icon="trash">Delete selected</x-ui.button>
            </div>
        </form>
        @endif

        @if ($products->isEmpty())
            <x-ui.empty-state title="No products found" description="Try different filters, or add your first product.">
                @can('create', App\Models\Product::class)
                    <x-ui.button :href="route('products.create')" icon="plus">New product</x-ui.button>
                @endcan
            </x-ui.empty-state>
        @else
            {{-- Table on tablets and up --}}
            <div class="hidden overflow-x-auto md:block">
                <table class="min-w-full divide-y divide-line text-sm">
                    <thead class="bg-surface-muted text-left text-xs font-semibold tracking-wide text-ink-muted uppercase">
                        <tr>
                            @if ($canBulkDelete)
                            <th class="w-10 px-4 py-3">
                                <input type="checkbox" aria-label="Select all on this page" class="h-4 w-4 rounded accent-brand"
                                       :checked="pageIds.length && selected.length === pageIds.length"
                                       @change="selected = $event.target.checked ? [...pageIds] : []">
                            </th>
                            @endif
                            <th class="px-4 py-3">Name</th>
                            <th class="px-4 py-3">Category</th>
                            <th class="px-4 py-3 text-right">Price (RM)</th>
                            <th class="px-4 py-3 text-right">Stock</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3 text-right"><span class="sr-only">Actions</span></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-line">
                        @foreach ($products as $product)
                            <tr class="hover:bg-surface-muted/60" :class="selected.includes({{ $product->id }}) && 'bg-brand-soft/50'">
                                @if ($canBulkDelete)
                                <td class="px-4 py-3">
                                    <input type="checkbox" value="{{ $product->id }}" x-model.number="selected"
                                           aria-label="Select {{ $product->name }}" class="h-4 w-4 rounded accent-brand">
                                </td>
                                @endif
                                <td class="px-4 py-3">
                                    <a href="{{ route('products.show', $product) }}" class="flex items-center gap-3 font-medium hover:text-brand">
                                        <x-product-image :product="$product" />
                                        <span>{{ $product->name }}</span>
                                    </a>
                                </td>
                                <td class="px-4 py-3 text-ink-muted">{{ $product->category?->name }}</td>
                                <td class="px-4 py-3 text-right tabular-nums">{{ number_format($product->price, 2) }}</td>
                                <td class="px-4 py-3 text-right"><x-stock-level :stock="$product->stock" /></td>
                                <td class="px-4 py-3"><x-status-badge :enabled="$product->enabled" /></td>
                                <td class="px-4 py-3">
                                    <div class="flex justify-end gap-1">
                                        @can('update', $product)
                                            <x-ui.button variant="ghost" size="sm" :href="route('products.edit', $product)">Edit</x-ui.button>
                                        @endcan
                                        @can('delete', $product)
                                        <form method="POST" action="{{ route('products.destroy', $product) }}"
                                              data-confirm="Move &quot;{{ $product->name }}&quot; to the trash? You can restore it later."
                                              data-confirm-title="Move to trash" data-confirm-label="Move to trash">
                                            @csrf
                                            @method('DELETE')
                                            <x-ui.button type="submit" variant="ghost" size="sm" class="hover:text-danger">Delete</x-ui.button>
                                        </form>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Cards on phones --}}
            <ul class="divide-y divide-line md:hidden">
                @foreach ($products as $product)
                    <li class="flex items-center gap-3 px-4 py-3">
                        @if ($canBulkDelete)
                        <input type="checkbox" value="{{ $product->id }}" x-model.number="selected"
                               aria-label="Select {{ $product->name }}" class="h-4 w-4 shrink-0 rounded accent-brand">
                        @endif
                        <a href="{{ route('products.show', $product) }}" class="flex min-w-0 flex-1 items-center gap-3">
                            <x-product-image :product="$product" size="h-12 w-12" />
                            <span class="min-w-0 flex-1">
                                <span class="block truncate font-medium">{{ $product->name }}</span>
                                <span class="block truncate text-xs text-ink-muted">{{ $product->category?->name }} · RM {{ number_format($product->price, 2) }}</span>
                            </span>
                        </a>
                        <div class="flex shrink-0 flex-col items-end gap-1">
                            <x-stock-level :stock="$product->stock" />
                            @unless ($product->enabled)
                                <x-ui.badge>Disabled</x-ui.badge>
                            @endunless
                        </div>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>

    <div class="mt-4">
        {{ $products->links() }}
    </div>
</x-layout>
