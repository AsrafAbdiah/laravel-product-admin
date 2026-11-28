@props([
    'variant' => 'full',   // full (mark + wordmark) | mark
    'class' => 'h-8',      // sets the mark's height; the width follows
])

{{--
    The BandarOS logo, redrawn as SVG from the brand sheet (docs/brand/logo-sheet.jpg).
    A spine, a B whose two counters read as shelves, and the terracotta bar
    running through the middle. The charcoal parts use currentColor, so the
    mark follows the text colour in light and dark mode.
--}}
<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-2.5 text-ink']) }}>
    <svg viewBox="0 0 240 315" xmlns="http://www.w3.org/2000/svg" class="{{ $class }} w-auto shrink-0" aria-hidden="true" focusable="false">
        {{-- Spine --}}
        <rect x="0" y="0" width="38" height="315" rx="5" fill="currentColor" />
        {{-- The B: open on the left, angled corners, pinched at the waist --}}
        <path d="M49 16H190L222 48V120L198 150L222 180V268L190 299H49" fill="none" stroke="currentColor" stroke-width="32" stroke-linejoin="miter" />
        {{-- Shelf counters --}}
        <path d="M66 128V55H136V90" fill="none" stroke="currentColor" stroke-width="14" stroke-linejoin="miter" />
        <path d="M66 270V180H136V218" fill="none" stroke="currentColor" stroke-width="14" stroke-linejoin="miter" />
        {{-- The terracotta bar and its arrow tip --}}
        <rect x="57" y="138" width="130" height="22" fill="var(--accent)" />
        <path d="M185 136L211 149L185 162Z" fill="var(--accent)" />
    </svg>

    @if ($variant === 'full')
        <span class="text-lg leading-none font-bold tracking-tight">Bandar<span class="text-accent">OS</span></span>
    @endif
</span>
