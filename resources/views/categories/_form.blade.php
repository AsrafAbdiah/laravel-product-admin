{{-- Shared by create and edit. Expects $category. --}}
<div>
    <label for="name" class="mb-1 block text-sm font-medium">Name <span class="text-red-500">*</span></label>
    <input id="name" name="name" type="text" value="{{ old('name', $category->name) }}" required maxlength="100" autofocus
           class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none">
    @error('name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
</div>
