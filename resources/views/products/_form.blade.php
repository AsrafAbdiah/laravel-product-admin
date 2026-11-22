{{-- Shared by create and edit. Expects $product and $categories. --}}
<div class="space-y-5">
    <x-ui.field label="Name" for="name" name="name" required>
        <x-ui.input name="name" type="text" :value="old('name', $product->name)" required maxlength="255" />
    </x-ui.field>

    <x-ui.field label="Category" for="category_id" name="category_id" required>
        <x-ui.select name="category_id" required>
            <option value="">Select a category</option>
            @foreach ($categories as $category)
                <option value="{{ $category->id }}" @selected((int) old('category_id', $product->category_id) === $category->id)>{{ $category->name }}</option>
            @endforeach
        </x-ui.select>
    </x-ui.field>

    <x-ui.field label="Description" for="description" name="description">
        <x-ui.textarea name="description" rows="4" maxlength="5000">{{ old('description', $product->description) }}</x-ui.textarea>
    </x-ui.field>

    <div class="grid gap-5 sm:grid-cols-2">
        <x-ui.field label="Price (RM)" for="price" name="price" required>
            <x-ui.input name="price" type="number" step="0.01" min="0" :value="old('price', $product->price)" required />
        </x-ui.field>

        <x-ui.field label="Stock" for="stock" name="stock" required>
            <x-ui.input name="stock" type="number" step="1" min="0" :value="old('stock', $product->stock ?? 0)" required />
        </x-ui.field>
    </div>

    <x-ui.field label="Image" for="image" name="image"
                :hint="'JPG, PNG or WebP, up to 2 MB.'.($product->image_path ? ' Uploading a new image replaces the current one.' : '')">
        <div class="flex flex-wrap items-center gap-4">
            @if ($product->exists)
                <x-product-image :product="$product" size="h-16 w-16" />
            @endif
            <div class="min-w-0 flex-1 space-y-2">
                <input id="image" name="image" type="file" accept="image/jpeg,image/png,image/webp"
                       class="block w-full text-sm text-ink-muted file:mr-3 file:rounded-lg file:border-0 file:bg-surface-muted file:px-3 file:py-2 file:text-sm file:font-medium file:text-ink hover:file:bg-line">
                @if ($product->image_path)
                    <label class="flex items-center gap-2 text-sm text-ink-muted">
                        <input type="checkbox" name="remove_image" value="1" class="h-4 w-4 rounded accent-brand" @checked(old('remove_image'))>
                        Remove current image
                    </label>
                @endif
            </div>
        </div>
    </x-ui.field>

    <x-ui.field name="enabled">
        {{-- The hidden input makes sure "0" is sent when the box is unticked. --}}
        <input type="hidden" name="enabled" value="0">
        <label class="flex items-start gap-3 rounded-lg border border-line p-3">
            <input type="checkbox" name="enabled" value="1" class="mt-0.5 h-4 w-4 rounded accent-brand"
                   @checked(old('enabled', $product->enabled ?? true))>
            <span>
                <span class="block text-sm font-medium">Enabled</span>
                <span class="block text-xs text-ink-muted">Visible to customers. Untick to hide it without deleting.</span>
            </span>
        </label>
    </x-ui.field>
</div>
