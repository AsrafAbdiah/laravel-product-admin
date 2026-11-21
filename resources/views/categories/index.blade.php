<x-layout title="Categories">
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-2xl font-semibold text-slate-900">Categories</h1>
            <p class="text-sm text-slate-500">{{ $categories->total() }} categor{{ $categories->total() === 1 ? 'y' : 'ies' }}</p>
        </div>
        <a href="{{ route('categories.create') }}"
           class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">
            + New Category
        </a>
    </div>

    <div class="overflow-hidden rounded-lg border border-slate-200 bg-white">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-left text-xs font-semibold tracking-wide text-slate-500 uppercase">
                    <tr>
                        <th class="px-4 py-3">Name</th>
                        <th class="px-4 py-3 text-right">Products</th>
                        <th class="px-4 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($categories as $category)
                        <tr class="hover:bg-slate-50">
                            <td class="px-4 py-3 font-medium text-slate-900">{{ $category->name }}</td>
                            <td class="px-4 py-3 text-right tabular-nums">
                                <a href="{{ route('products.index', ['category_id' => $category->id]) }}" class="text-indigo-600 hover:text-indigo-800">
                                    {{ $category->products_count }}
                                </a>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex justify-end gap-3">
                                    <a href="{{ route('categories.edit', $category) }}" class="text-indigo-600 hover:text-indigo-800">Edit</a>
                                    <form method="POST" action="{{ route('categories.destroy', $category) }}"
                                          onsubmit="return confirm('Delete this category?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:text-red-800">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="px-4 py-10 text-center text-slate-500">
                                No categories yet. <a href="{{ route('categories.create') }}" class="text-indigo-600 hover:underline">Create one</a>.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <p class="mt-3 text-xs text-slate-500">A category can only be deleted when it has no products, including products in the trash.</p>

    <div class="mt-4">
        {{ $categories->links() }}
    </div>
</x-layout>
