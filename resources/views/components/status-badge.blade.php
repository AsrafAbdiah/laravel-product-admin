@props(['enabled'])

@if ($enabled)
    <x-ui.badge tone="success">Enabled</x-ui.badge>
@else
    <x-ui.badge>Disabled</x-ui.badge>
@endif
