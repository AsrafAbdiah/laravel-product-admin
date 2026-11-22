<x-layout title="New product">
    <div class="mx-auto max-w-2xl">
        <x-ui.page-header title="New product" :back="route('products.index')" back-label="Products" />

        <form method="POST" action="{{ route('products.store') }}" enctype="multipart/form-data">
            @csrf
            <x-ui.card>
                @include('products._form')
            </x-ui.card>

            <div class="mt-4 flex justify-end gap-2">
                <x-ui.button variant="secondary" :href="route('products.index')">Cancel</x-ui.button>
                <x-ui.button type="submit">Create product</x-ui.button>
            </div>
        </form>
    </div>
</x-layout>
