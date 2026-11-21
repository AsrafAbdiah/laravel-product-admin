{{-- Shared by create and edit. Expects $product and $categories. --}}
@php
    $input = 'w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none';
@endphp

<div class="space-y-5">
    <div>
        <label for="name" class="mb-1 block text-sm font-medium">Name <span class="text-red-500">*</span></label>
        <input id="name" name="name" type="text" value="{{ old('name', $product->name) }}" required maxlength="255" class="{{ $input }}">
        @error('name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="category_id" class="mb-1 block text-sm font-medium">Category <span class="text-red-500">*</span></label>
        <select id="category_id" name="category_id" required class="{{ $input }}">
            <option value="">Select a category</option>
            @foreach ($categories as $category)
                <option value="{{ $category->id }}" @selected((int) old('category_id', $product->category_id) === $category->id)>{{ $category->name }}</option>
            @endforeach
        </select>
        @error('category_id') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="description" class="mb-1 block text-sm font-medium">Description</label>
        <textarea id="description" name="description" rows="4" maxlength="5000" class="{{ $input }}">{{ old('description', $product->description) }}</textarea>
        @error('description') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>

    <div class="grid gap-5 sm:grid-cols-2">
        <div>
            <label for="price" class="mb-1 block text-sm font-medium">Price (RM) <span class="text-red-500">*</span></label>
            <input id="price" name="price" type="number" step="0.01" min="0" value="{{ old('price', $product->price) }}" required class="{{ $input }}">
            @error('price') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="stock" class="mb-1 block text-sm font-medium">Stock <span class="text-red-500">*</span></label>
            <input id="stock" name="stock" type="number" step="1" min="0" value="{{ old('stock', $product->stock ?? 0) }}" required class="{{ $input }}">
            @error('stock') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>
    </div>

    <div>
        <label for="image" class="mb-1 block text-sm font-medium">Image</label>
        <div class="flex flex-wrap items-center gap-4">
            @if ($product->exists)
                <x-product-image :product="$product" size="h-16 w-16" />
            @endif
            <div class="min-w-0 flex-1 space-y-2">
                <input id="image" name="image" type="file" accept="image/jpeg,image/png,image/webp"
                       class="block w-full text-sm text-slate-600 file:mr-3 file:rounded-md file:border-0 file:bg-slate-100 file:px-3 file:py-2 file:text-sm file:font-medium hover:file:bg-slate-200">
                <p class="text-xs text-slate-500">
                    JPG, PNG or WebP, up to 2 MB.
                    @if ($product->image_path)
                        Uploading a new image replaces the current one.
                    @endif
                </p>
                @if ($product->image_path)
                    <label class="flex items-center gap-2 text-sm text-slate-600">
                        <input type="checkbox" name="remove_image" value="1" class="rounded border-slate-300" @checked(old('remove_image'))>
                        Remove current image
                    </label>
                @endif
            </div>
        </div>
        @error('image') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>

    <div>
        {{-- The hidden input makes sure "0" is sent when the box is unticked. --}}
        <input type="hidden" name="enabled" value="0">
        <label class="flex items-center gap-2 text-sm font-medium">
            <input type="checkbox" name="enabled" value="1" class="rounded border-slate-300"
                   @checked(old('enabled', $product->enabled ?? true))>
            Enabled (visible to customers)
        </label>
        @error('enabled') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>
</div>
