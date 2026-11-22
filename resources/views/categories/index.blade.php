<x-layout title="Categories">
    <x-ui.page-header title="Categories" :description="$categories->total().' '.Str::plural('category', $categories->total())">
        @can('create', App\Models\Category::class)
            <x-slot:actions>
                <x-ui.button :href="route('categories.create')" icon="plus">New category</x-ui.button>
            </x-slot:actions>
        @endcan
    </x-ui.page-header>

    <x-ui.card :padding="false">
        @if ($categories->isEmpty())
            <x-ui.empty-state icon="tag" title="No categories yet." description="Categories group your products, e.g. Drinks or Snacks.">
                @can('create', App\Models\Category::class)
                    <x-ui.button :href="route('categories.create')" icon="plus">New category</x-ui.button>
                @endcan
            </x-ui.empty-state>
        @else
            <ul class="divide-y divide-line">
                @foreach ($categories as $category)
                    <li class="flex items-center gap-3 px-5 py-3.5">
                        <span class="grid h-9 w-9 shrink-0 place-items-center rounded-lg bg-brand-soft text-brand-ink">
                            <x-ui.icon name="tag" class="h-4 w-4" />
                        </span>
                        <div class="min-w-0 flex-1">
                            <p class="truncate font-medium">{{ $category->name }}</p>
                            <a href="{{ route('products.index', ['category_id' => $category->id]) }}" class="text-xs text-ink-muted hover:text-brand">
                                {{ $category->products_count }} {{ Str::plural('product', $category->products_count) }}
                            </a>
                        </div>
                        @can('update', $category)
                        <div class="flex gap-1">
                            <x-ui.button variant="ghost" size="sm" :href="route('categories.edit', $category)">Edit</x-ui.button>
                            <form method="POST" action="{{ route('categories.destroy', $category) }}"
                                  data-confirm="Delete the category &quot;{{ $category->name }}&quot;?" data-confirm-title="Delete category" data-confirm-label="Delete">
                                @csrf
                                @method('DELETE')
                                <x-ui.button type="submit" variant="ghost" size="sm" class="hover:text-danger">Delete</x-ui.button>
                            </form>
                        </div>
                        @endcan
                    </li>
                @endforeach
            </ul>
            @if ($categories->hasPages())
                <div class="border-t border-line px-5 py-3">{{ $categories->links() }}</div>
            @endif
        @endif
    </x-ui.card>

    <p class="mt-3 text-xs text-ink-subtle">A category can only be deleted when it has no products, including products in the trash.</p>
</x-layout>
