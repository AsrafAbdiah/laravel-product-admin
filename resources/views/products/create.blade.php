<x-layout title="New Product">
    <div class="mx-auto max-w-2xl">
        <a href="{{ route('products.index') }}" class="text-sm text-slate-500 hover:text-slate-800">&larr; Back to products</a>
        <h1 class="mt-2 mb-6 text-2xl font-semibold text-slate-900">New Product</h1>

        <form method="POST" action="{{ route('products.store') }}" class="rounded-lg border border-slate-200 bg-white p-6">
            @csrf
            @include('products._form')

            <div class="mt-6 flex justify-end gap-2">
                <a href="{{ route('products.index') }}" class="rounded-md border border-slate-300 px-4 py-2 text-sm hover:bg-slate-50">Cancel</a>
                <button type="submit" class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">Create Product</button>
            </div>
        </form>
    </div>
</x-layout>
