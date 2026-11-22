@props(['name', 'invalid' => null])

@php
    $invalid ??= $errors->has($name);
@endphp

<select name="{{ $name }}" id="{{ $attributes->get('id', $name) }}"
        @if ($invalid) aria-invalid="true" @endif
        {{ $attributes->except('id')->merge(['class' => 'block w-full rounded-lg border bg-surface px-3 py-2 text-sm text-ink focus:outline-2 focus:outline-offset-0 focus:outline-brand '.($invalid ? 'border-danger' : 'border-line-strong')]) }}>
    {{ $slot }}
</select>
