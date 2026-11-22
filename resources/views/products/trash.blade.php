<x-layout title="Trash">
    <x-ui.page-header title="Trash" :description="$products->total().' deleted product(s). Restore them, or delete them forever.'" />

    <form method="GET" action="{{ route('products.trash') }}" class="mb-4 flex flex-wrap gap-2">
        <div class="relative min-w-0 flex-1">
            <x-ui.icon name="search" class="pointer-events-none absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-ink-subtle" />
            <x-ui.input name="search" type="search" :value="$filters['search']" placeholder="Search deleted products..." aria-label="Search deleted products" class="pl-9" />
        </div>
        <x-ui.button type="submit" variant="secondary">Search</x-ui.button>
        @if ($filters['search'])
            <x-ui.button variant="ghost" :href="route('products.trash')">Reset</x-ui.button>
        @endif
    </form>

    <x-ui.card :padding="false">
        @if ($products->isEmpty())
            <x-ui.empty-state icon="trash" title="The trash is empty." description="Products you delete land here first, so they can be restored." />
        @else
            <ul class="divide-y divide-line">
                @foreach ($products as $product)
                    <li class="flex flex-wrap items-center gap-3 px-5 py-3.5">
                        <x-product-image :product="$product" />
                        <div class="min-w-0 flex-1">
                            <p class="truncate font-medium">{{ $product->name }}</p>
                            <p class="truncate text-xs text-ink-muted">
                                {{ $product->category?->name }} · RM {{ number_format($product->price, 2) }} · {{ $product->stock }} in stock ·
                                <span title="{{ $product->deleted_at->format('d M Y, H:i') }}">deleted {{ $product->deleted_at->diffForHumans() }}</span>
                            </p>
                        </div>
                        <div class="flex gap-2">
                            <form method="POST" action="{{ route('products.restore', $product) }}">
                                @csrf
                                @method('PATCH')
                                <x-ui.button type="submit" variant="secondary" size="sm" icon="restore">Restore</x-ui.button>
                            </form>
                            <form method="POST" action="{{ route('products.force-destroy', $product) }}"
                                  data-confirm="Permanently delete &quot;{{ $product->name }}&quot;? Its image and stock history are removed too. This cannot be undone."
                                  data-confirm-title="Delete forever" data-confirm-label="Delete forever">
                                @csrf
                                @method('DELETE')
                                <x-ui.button type="submit" variant="ghost" size="sm" class="text-danger hover:text-danger">Delete forever</x-ui.button>
                            </form>
                        </div>
                    </li>
                @endforeach
            </ul>
            @if ($products->hasPages())
                <div class="border-t border-line px-5 py-3">{{ $products->links() }}</div>
            @endif
        @endif
    </x-ui.card>
</x-layout>
