<x-layout title="Edit {{ $product->name }}">
    <div class="mx-auto max-w-2xl">
        <a href="{{ route('products.show', $product) }}" class="text-sm text-slate-500 hover:text-slate-800">&larr; Back to product</a>
        <h1 class="mt-2 mb-6 text-2xl font-semibold text-slate-900">Edit Product</h1>

        <form method="POST" action="{{ route('products.update', $product) }}" class="rounded-lg border border-slate-200 bg-white p-6">
            @csrf
            @method('PUT')
            @include('products._form')

            <div class="mt-6 flex justify-end gap-2">
                <a href="{{ route('products.show', $product) }}" class="rounded-md border border-slate-300 px-4 py-2 text-sm hover:bg-slate-50">Cancel</a>
                <button type="submit" class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">Save Changes</button>
            </div>
        </form>
    </div>
</x-layout>
