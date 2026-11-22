@props(['product', 'size' => 'h-10 w-10'])

{{-- The product photo, or a neutral placeholder when there is none. --}}
@if ($product->image_url)
    <img src="{{ $product->image_url }}" alt="{{ $product->name }}" loading="lazy"
         {{ $attributes->merge(['class' => "$size shrink-0 rounded-lg border border-line object-cover"]) }}>
@else
    <div {{ $attributes->merge(['class' => "$size shrink-0 rounded-lg border border-dashed border-line-strong bg-surface-muted text-ink-subtle grid place-items-center"]) }}
         role="img" aria-label="No image">
        <x-ui.icon name="image" class="h-1/2 w-1/2" />
    </div>
@endif
