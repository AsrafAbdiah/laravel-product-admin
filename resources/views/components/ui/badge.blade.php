@props(['tone' => 'neutral'])  {{-- neutral | brand | success | warning | danger --}}

@php
    $tones = [
        'neutral' => 'bg-surface-muted text-ink-muted',
        'brand' => 'bg-brand-soft text-brand-ink',
        'success' => 'bg-success-soft text-success',
        'warning' => 'bg-warning-soft text-warning',
        'danger' => 'bg-danger-soft text-danger',
    ];
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs font-medium whitespace-nowrap tabular-nums '.($tones[$tone] ?? $tones['neutral'])]) }}>
    {{ $slot }}
</span>
