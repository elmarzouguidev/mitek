@props(['product'])

@if (! $product->isInStock())
    <span class="inline-flex items-center rounded-full bg-red-50 px-2 py-0.5 text-xs font-medium text-red-700 ring-1 ring-red-200">{{ __('catalog.stock.out') }}</span>
@elseif ($product->isLowStock())
    <span class="inline-flex items-center rounded-full bg-amber-50 px-2 py-0.5 text-xs font-medium text-amber-800 ring-1 ring-amber-200">{{ __('catalog.stock.low') }}</span>
@else
    <span class="inline-flex items-center rounded-full bg-emerald-50 px-2 py-0.5 text-xs font-medium text-emerald-700 ring-1 ring-emerald-200">{{ __('catalog.stock.in') }}</span>
@endif
