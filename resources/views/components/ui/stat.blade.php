@props(['label', 'value', 'hint' => null, 'href' => null, 'tone' => 'neutral'])  {{-- tone: neutral | warning --}}

@php
    $toneClasses = $tone === 'warning'
        ? 'border-warning/40 bg-warning-soft'
        : 'border-line bg-surface';
    $tag = $href ? 'a' : 'div';
@endphp

<{{ $tag }} @if ($href) href="{{ $href }}" @endif
    {{ $attributes->merge(['class' => "block rounded-xl border p-4 shadow-xs $toneClasses".($href ? ' transition-colors hover:border-brand' : '')]) }}>
    <p class="text-xs font-semibold tracking-wide uppercase {{ $tone === 'warning' ? 'text-warning' : 'text-ink-muted' }}">{{ $label }}</p>
    <p class="mt-1 text-3xl font-semibold tracking-tight text-ink tabular-nums">{{ $value }}</p>
    @if ($hint)
        <p class="mt-1 text-xs {{ $tone === 'warning' ? 'text-warning' : 'text-ink-muted' }}">{{ $hint }}</p>
    @endif
</{{ $tag }}>
