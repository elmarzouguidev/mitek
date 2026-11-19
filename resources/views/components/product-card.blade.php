@props(['product', 'level' => 3])

@php
    $calculator = app(\App\Services\Core\Product\PriceCalculator::class);
    $whatsappLink = app(\App\Support\WhatsApp::class)->productLink($product);
@endphp

<article class="group flex flex-col overflow-hidden rounded-xl bg-white ring-1 ring-slate-200 transition hover:ring-brand-300">
    <a href="{{ route('products.show', $product->slug) }}" class="block aspect-square bg-slate-50">
        <x-product-image :product="$product" conversion="card" class="size-full object-contain p-4" loading="lazy" />
    </a>
    <div class="flex flex-1 flex-col p-4">
        @if ($product->brand)
            <p class="text-xs font-medium text-brand-600">{{ $product->brand->name }}</p>
        @endif
        <h{{ $level }} class="mt-0.5 text-sm font-semibold text-slate-900">
            <a href="{{ route('products.show', $product->slug) }}" class="hover:text-brand-700">{{ $product->name }}</a>
        </h{{ $level }}>
        <p class="mt-0.5 text-xs text-slate-500">{{ __('catalog.reference') }} {{ $product->reference }}</p>

        <div class="mt-auto flex items-end justify-between gap-2 pt-4">
            <div>
                <p class="text-lg font-bold text-brand-900">{{ $calculator->unitPriceTtc($product) }} <span class="text-xs font-medium text-slate-500">{{ __('catalog.ttc') }}</span></p>
                <p class="text-xs text-slate-500">{{ $calculator->unitPriceHt($product) }} {{ __('catalog.ht') }}</p>
            </div>
            <x-stock-badge :product="$product" />
        </div>

        @if ($whatsappLink)
            <a href="{{ $whatsappLink }}" target="_blank" rel="noopener noreferrer" class="mt-3 inline-flex items-center justify-center gap-1.5 rounded-lg px-3 py-2 text-xs font-semibold text-emerald-700 ring-1 ring-inset ring-emerald-200 transition hover:bg-emerald-50 active:scale-[0.98]">
                <svg class="size-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12.04 2a9.9 9.9 0 0 0-8.4 15.1L2 22l5.05-1.6A9.9 9.9 0 1 0 12.04 2Zm4.5 12c-.25-.12-1.45-.72-1.68-.8-.22-.08-.39-.12-.55.12s-.63.8-.77.96-.28.18-.52.06a6.7 6.7 0 0 1-3.3-2.9c-.25-.43.25-.4.72-1.33.08-.16.04-.3-.02-.42s-.55-1.32-.75-1.8c-.2-.47-.4-.4-.55-.41h-.47c-.16 0-.42.06-.64.3s-.84.82-.84 2 .86 2.32.98 2.48c.12.16 1.7 2.6 4.1 3.65 1.5.65 2.1.7 2.86.6.46-.07 1.45-.6 1.65-1.17s.2-1.07.14-1.17-.22-.16-.47-.28Z"/></svg>
                {{ __('app.whatsapp.order') }}<span class="sr-only"> : {{ $product->name }} {{ __('app.whatsapp.new_tab') }}</span>
            </a>
        @endif
    </div>
</article>
