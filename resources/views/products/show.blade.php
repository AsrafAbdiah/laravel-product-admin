<x-layout :title="$product->name">
    <div class="mx-auto max-w-2xl">
        <a href="{{ route('products.index') }}" class="text-sm text-slate-500 hover:text-slate-800">&larr; Back to products</a>

        <div class="mt-2 mb-6 flex flex-wrap items-start justify-between gap-3">
            <div>
                <h1 class="text-2xl font-semibold text-slate-900">{{ $product->name }}</h1>
                <p class="text-sm text-slate-500">{{ $product->category?->name }}</p>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('products.edit', $product) }}" class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">Edit</a>
                <form method="POST" action="{{ route('products.destroy', $product) }}" onsubmit="return confirm('Delete this product?')">
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
    </div>
</x-layout>
