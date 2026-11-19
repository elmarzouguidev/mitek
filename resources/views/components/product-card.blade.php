@props(['product'])

@php($calculator = app(\App\Services\Core\Product\PriceCalculator::class))

<article class="group flex flex-col overflow-hidden rounded-xl bg-white ring-1 ring-slate-200 transition hover:ring-brand-300">
    <a href="{{ route('products.show', $product->slug) }}" class="block aspect-square bg-slate-50">
        <x-product-image :product="$product" conversion="card" class="size-full object-contain p-4" loading="lazy" />
    </a>
    <div class="flex flex-1 flex-col p-4">
        @if ($product->brand)
            <p class="text-xs font-medium text-brand-600">{{ $product->brand->name }}</p>
        @endif
        <h3 class="mt-0.5 text-sm font-semibold text-slate-900">
            <a href="{{ route('products.show', $product->slug) }}" class="hover:text-brand-700">{{ $product->name }}</a>
        </h3>
        <p class="mt-0.5 text-xs text-slate-500">{{ __('catalog.reference') }} {{ $product->reference }}</p>

        <div class="mt-auto flex items-end justify-between gap-2 pt-4">
            <div>
                <p class="text-lg font-bold text-brand-900">{{ $calculator->unitPriceTtc($product) }} <span class="text-xs font-medium text-slate-500">{{ __('catalog.ttc') }}</span></p>
                <p class="text-xs text-slate-500">{{ $calculator->unitPriceHt($product) }} {{ __('catalog.ht') }}</p>
            </div>
            <x-stock-badge :product="$product" />
        </div>
    </div>
</article>
