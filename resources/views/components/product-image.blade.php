@props(['product', 'size' => 'h-10 w-10'])

{{-- The product photo, or a neutral placeholder when there is none. --}}
@if ($product->image_url)
    <img src="{{ $product->image_url }}" alt="{{ $product->name }}" loading="lazy"
         {{ $attributes->merge(['class' => "$size shrink-0 rounded-md border border-slate-200 object-cover"]) }}>
@else
    <div {{ $attributes->merge(['class' => "$size shrink-0 rounded-md border border-dashed border-slate-300 bg-slate-100 text-slate-400 grid place-items-center"]) }}
         role="img" aria-label="No image">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="h-1/2 w-1/2" aria-hidden="true">
            <rect x="3" y="4" width="18" height="16" rx="2" />
            <circle cx="8.5" cy="9.5" r="1.5" />
            <path d="m21 16-5-5-8 8" />
        </svg>
    </div>
@endif
