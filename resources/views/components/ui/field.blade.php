@props(['label' => null, 'for' => null, 'name' => null, 'hint' => null, 'required' => false])

{{-- A form row: label, the control (slot), a hint and the validation error for $name. --}}
<div {{ $attributes }}>
    @if ($label)
        <label for="{{ $for }}" class="mb-1.5 block text-sm font-medium text-ink">
            {{ $label }}
            @if ($required) <span class="text-danger" aria-hidden="true">*</span> @endif
        </label>
    @endif

    {{ $slot }}

    @if ($hint)
        <p class="mt-1.5 text-xs text-ink-subtle">{{ $hint }}</p>
    @endif

    @if ($name)
        @error($name)
            <p class="mt-1.5 text-sm text-danger">{{ $message }}</p>
        @enderror
    @endif
</div>
