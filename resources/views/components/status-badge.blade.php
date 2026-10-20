@props(['enabled'])

@if ($enabled)
    <span class="inline-flex rounded-full bg-green-100 px-2 py-0.5 text-xs font-medium text-green-800">Enabled</span>
@else
    <span class="inline-flex rounded-full bg-slate-200 px-2 py-0.5 text-xs font-medium text-slate-700">Disabled</span>
@endif
