<x-layout :title="'Edit '.$product->name">
    <div class="mx-auto max-w-2xl">
        <x-ui.page-header title="Edit product" :description="$product->name" :back="route('products.show', $product)" back-label="Back to product" />

        <form method="POST" action="{{ route('products.update', $product) }}" enctype="multipart/form-data">
            @csrf
            @method('PUT')
            <x-ui.card>
                @include('products._form')
            </x-ui.card>

            <div class="mt-4 flex justify-end gap-2">
                <x-ui.button variant="secondary" :href="route('products.show', $product)">Cancel</x-ui.button>
                <x-ui.button type="submit">Save changes</x-ui.button>
            </div>
        </form>
    </div>
</x-layout>
