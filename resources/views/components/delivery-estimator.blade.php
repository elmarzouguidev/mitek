@props(['product' => null, 'cart' => false, 'weightGrams' => 0, 'quantitySelector' => null])

@php
    $estimator = app(\App\Services\Core\Shipping\DeliveryEstimator::class);
    $city = trim((string) request('ville'));
    $estimate = null;
    $failed = false;

    if ($city !== '') {
        try {
            $estimate = $estimator->estimate($city, $weightGrams);
        } catch (\App\Exceptions\ShippingUnavailable) {
            $failed = true;
        }
    }

    $id = 'delivery-'.($cart ? 'cart' : 'product');
    $input = 'block w-full rounded-lg border-0 bg-white px-3 py-2 text-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-brand-600';
@endphp

<section
    id="livraison"
    {{ $attributes->class('scroll-mt-28') }}
    x-data="deliveryEstimator({ url: @js(route('delivery.estimate')), product: @js($product), cart: @js($cart), quantitySelector: @js($quantitySelector), city: @js($city), hasResult: @js($estimate !== null || $failed), errorMessage: @js(__('delivery.unavailable')) })"
>
    <p class="flex items-center gap-2 text-sm font-medium text-slate-900">
        <svg class="size-5 shrink-0 text-brand-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 0 1-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.9 17.9 0 0 0-3.213-9.193 2.06 2.06 0 0 0-1.58-.86H14.25M16.5 18.75h-2.25m0-11.177v-.958c0-.568-.422-1.048-.987-1.106a48.5 48.5 0 0 0-10.026 0 1.106 1.106 0 0 0-.987 1.106v7.635m12-6.677v6.677m0 4.5v-4.5m0 0h-12"/></svg>
        {{ __('delivery.everywhere') }}
    </p>
    <p class="mt-1 text-sm text-slate-600">{{ __('delivery.headline', ['dispatch' => $estimator->dispatchText()]) }}</p>

    <form method="GET" action="{{ url()->current() }}#livraison" class="mt-3 flex items-end gap-2" @submit.prevent="submit()">
        <div class="min-w-0 flex-1">
            <label for="{{ $id }}-city" class="block text-xs font-medium text-slate-600">{{ __('delivery.city') }}</label>
            <input id="{{ $id }}-city" name="ville" type="text" list="{{ $id }}-cities" x-model="city" value="{{ $city }}" maxlength="100" autocomplete="address-level2" placeholder="{{ __('delivery.city_cta') }}" class="{{ $input }}">
            <datalist id="{{ $id }}-cities">
                @foreach ($estimator->cities() as $suggestion)
                    <option value="{{ $suggestion }}"></option>
                @endforeach
            </datalist>
        </div>
        <button type="submit" class="shrink-0 rounded-lg px-4 py-2 text-sm font-semibold text-brand-700 ring-1 ring-inset ring-brand-200 transition hover:bg-brand-50 active:scale-[0.98]">{{ __('delivery.calculate') }}</button>
    </form>

    <div class="mt-3 text-sm" aria-live="polite">
        {{-- Server-rendered result (no JavaScript, or before the first request from the page). --}}
        <div x-show="!live">
            @if ($estimate)
                @php($lines = $estimate->lines())
                <p class="font-semibold text-brand-900">{{ $lines['estimate'] }}</p>
                <p class="text-slate-600">{{ $lines['transit'] }}</p>
                <p class="text-slate-600">{{ $lines['fee'] }}</p>
                @if ($lines['notice'])<p class="text-amber-800">{{ $lines['notice'] }}</p>@endif
                <p class="mt-1 text-xs text-slate-500">{{ $lines['hint'] }}</p>
            @elseif ($failed)
                <p class="text-red-700">{{ __('delivery.unavailable') }}</p>
            @endif
        </div>

        <div x-cloak x-show="live">
            <p x-show="loading" class="text-slate-500">{{ __('delivery.loading') }}</p>
            <div x-show="result && !loading">
                <p class="font-semibold text-brand-900" x-text="result?.lines.estimate"></p>
                <p class="text-slate-600" x-text="result?.lines.transit"></p>
                <p class="text-slate-600" x-text="result?.lines.fee"></p>
                <p x-show="result?.lines.notice" class="text-amber-800" x-text="result?.lines.notice"></p>
                <p class="mt-1 text-xs text-slate-500" x-text="result?.lines.hint"></p>
            </div>
            <p x-show="error && !loading" class="text-red-700" x-text="error"></p>
        </div>
    </div>
</section>
