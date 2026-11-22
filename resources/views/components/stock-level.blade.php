@props(['stock'])

{{-- Stock as a number, highlighted when it is low or zero. --}}
@if ($stock === 0)
    <x-ui.badge tone="danger">Out of stock</x-ui.badge>
@elseif ($stock <= \App\Models\Product::LOW_STOCK_THRESHOLD)
    <x-ui.badge tone="warning">{{ $stock }} left</x-ui.badge>
@else
    <span class="tabular-nums">{{ $stock }}</span>
@endif
