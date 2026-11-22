<x-layout :title="'Edit '.$category->name">
    <div class="mx-auto max-w-xl">
        <x-ui.page-header title="Edit category" :description="$category->name" :back="route('categories.index')" back-label="Categories" />

        <form method="POST" action="{{ route('categories.update', $category) }}">
            @csrf
            @method('PUT')
            <x-ui.card>
                @include('categories._form')
            </x-ui.card>

            <div class="mt-4 flex justify-end gap-2">
                <x-ui.button variant="secondary" :href="route('categories.index')">Cancel</x-ui.button>
                <x-ui.button type="submit">Save changes</x-ui.button>
            </div>
        </form>
    </div>
</x-layout>
