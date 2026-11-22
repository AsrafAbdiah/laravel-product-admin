{{-- Shared by create and edit. Expects $category. --}}
<x-ui.field label="Name" for="name" name="name" required>
    <x-ui.input name="name" type="text" :value="old('name', $category->name)" required maxlength="100" autofocus />
</x-ui.field>
