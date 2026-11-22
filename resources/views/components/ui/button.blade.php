@props([
    'variant' => 'primary',   // primary | secondary | danger | ghost
    'size' => 'md',           // sm | md
    'href' => null,           // renders a link styled as a button
    'type' => 'button',
    'icon' => null,
])

@php
    $base = 'inline-flex items-center justify-center gap-2 rounded-lg font-medium whitespace-nowrap transition-colors focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand disabled:cursor-not-allowed disabled:opacity-50';

    $sizes = [
        'sm' => 'px-2.5 py-1.5 text-xs',
        'md' => 'px-4 py-2 text-sm',
    ];

    $variants = [
        'primary' => 'bg-brand text-on-brand hover:bg-brand-hover shadow-sm',
        'secondary' => 'border border-line-strong bg-surface text-ink hover:bg-surface-muted',
        'danger' => 'bg-danger text-white hover:bg-danger-hover shadow-sm',
        'ghost' => 'text-ink-muted hover:bg-surface-muted hover:text-ink',
    ];

    $classes = $base.' '.($sizes[$size] ?? $sizes['md']).' '.($variants[$variant] ?? $variants['primary']);
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>
        @if ($icon) <x-ui.icon :name="$icon" class="h-4 w-4" /> @endif
        {{ $slot }}
    </a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $classes]) }}>
        @if ($icon) <x-ui.icon :name="$icon" class="h-4 w-4" /> @endif
        {{ $slot }}
    </button>
@endif
