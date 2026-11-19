@php
    $input = 'mt-1 block w-full rounded-lg border-0 px-3 py-2 text-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-brand-600';
    $initialPayment = old('payment_method', '');
@endphp

<x-layouts.app :title="__('orders.checkout.title')">
    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8"
         x-data="checkout({ url: '{{ route('checkout.estimate') }}', city: @js(old('shipping_city', '')), carrier: @js(old('carrier_mode', 'mitek')), payment: @js($initialPayment), isCompany: @js((bool) old('is_company')) })">
        <h1 class="text-3xl font-bold tracking-tight text-brand-900">{{ __('orders.checkout.title') }}</h1>

        @foreach ($summary->adjustments as $message)
            <p class="mt-4 rounded-lg bg-amber-50 px-4 py-3 text-sm text-amber-900 ring-1 ring-amber-200" role="status">{{ $message }}</p>
        @endforeach

        @guest
            <p class="mt-2 text-sm text-slate-600">{{ __('orders.checkout.login_hint') }} <a href="{{ route('login') }}" class="font-medium text-brand-700 hover:underline">{{ __('orders.checkout.login') }}</a></p>
        @endguest

        <form method="POST" action="{{ route('checkout.store') }}" class="mt-8 grid gap-8 lg:grid-cols-[1fr_24rem]">
            @csrf
            <x-honeypot />

            <div class="space-y-8">
                <section class="space-y-4" aria-labelledby="contact-title">
                    <h2 id="contact-title" class="text-xl font-semibold text-brand-900">{{ __('orders.checkout.contact') }}</h2>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="sm:col-span-2">
                            <label for="contact_name" class="block text-sm font-medium text-slate-700">{{ __('orders.checkout.name') }}</label>
                            <input id="contact_name" name="contact_name" type="text" value="{{ old('contact_name', $user?->name) }}" required autocomplete="name" class="{{ $input }}">
                            @error('contact_name')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="contact_email" class="block text-sm font-medium text-slate-700">{{ __('orders.checkout.email') }}</label>
                            <input id="contact_email" name="contact_email" type="email" value="{{ old('contact_email', $user?->email) }}" required autocomplete="email" class="{{ $input }}">
                            @error('contact_email')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="contact_phone" class="block text-sm font-medium text-slate-700">{{ __('orders.checkout.phone') }}</label>
                            <input id="contact_phone" name="contact_phone" type="tel" value="{{ old('contact_phone') }}" required autocomplete="tel" class="{{ $input }}">
                            <p class="mt-1 text-xs text-slate-500">{{ __('orders.checkout.phone_hint') }}</p>
                            @error('contact_phone')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                        </div>
                    </div>

                    <label class="flex items-center gap-2 text-sm text-slate-700">
                        <input type="hidden" name="is_company" value="0">
                        <input type="checkbox" name="is_company" value="1" x-model="isCompany" class="rounded text-brand-700 focus:ring-brand-600">
                        {{ __('orders.checkout.company') }}
                    </label>

                    <div x-cloak x-show="isCompany" class="grid gap-4 sm:grid-cols-2">
                        <div class="sm:col-span-2">
                            <label for="company_name" class="block text-sm font-medium text-slate-700">{{ __('orders.checkout.company_name') }}</label>
                            <input id="company_name" name="company_name" type="text" value="{{ old('company_name') }}" class="{{ $input }}">
                            @error('company_name')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="ice" class="block text-sm font-medium text-slate-700">{{ __('orders.checkout.ice') }}</label>
                            <input id="ice" name="ice" type="text" inputmode="numeric" maxlength="15" value="{{ old('ice') }}" class="{{ $input }}">
                            @error('ice')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="if_number" class="block text-sm font-medium text-slate-700">{{ __('orders.checkout.if') }}</label>
                            <input id="if_number" name="if_number" type="text" value="{{ old('if_number') }}" class="{{ $input }}">
                        </div>
                        <div>
                            <label for="rc_number" class="block text-sm font-medium text-slate-700">{{ __('orders.checkout.rc') }}</label>
                            <input id="rc_number" name="rc_number" type="text" value="{{ old('rc_number') }}" class="{{ $input }}">
                        </div>
                    </div>
                </section>

                <section class="space-y-4" aria-labelledby="delivery-title">
                    <h2 id="delivery-title" class="text-xl font-semibold text-brand-900">{{ __('orders.checkout.delivery') }}</h2>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label for="shipping_city" class="block text-sm font-medium text-slate-700">{{ __('orders.checkout.city') }}</label>
                            <input id="shipping_city" name="shipping_city" type="text" list="cities" x-model="city" @input="onChange()" @change="onChange()" required autocomplete="address-level2" class="{{ $input }}">
                            <datalist id="cities">
                                @foreach ($cities as $city)
                                    <option value="{{ $city }}"></option>
                                @endforeach
                            </datalist>
                            @error('shipping_city')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="shipping_postal_code" class="block text-sm font-medium text-slate-700">{{ __('orders.checkout.postal_code') }}</label>
                            <input id="shipping_postal_code" name="shipping_postal_code" type="text" value="{{ old('shipping_postal_code') }}" autocomplete="postal-code" class="{{ $input }}">
                        </div>
                        <div class="sm:col-span-2">
                            <label for="shipping_address" class="block text-sm font-medium text-slate-700">{{ __('orders.checkout.address') }}</label>
                            <textarea id="shipping_address" name="shipping_address" rows="2" required autocomplete="street-address" class="{{ $input }}">{{ old('shipping_address') }}</textarea>
                            @error('shipping_address')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                        </div>
                    </div>

                    <fieldset>
                        <legend class="text-sm font-medium text-slate-700">{{ __('orders.checkout.carrier') }}</legend>
                        <div class="mt-2 space-y-2">
                            @foreach (\App\Enums\CarrierMode::cases() as $mode)
                                <label class="flex items-center gap-2 text-sm text-slate-700">
                                    <input type="radio" name="carrier_mode" value="{{ $mode->value }}" x-model="carrier" @change="onChange()" class="text-brand-700 focus:ring-brand-600">
                                    {{ $mode->label() }}
                                </label>
                            @endforeach
                        </div>
                        @error('carrier_mode')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </fieldset>

                    <div x-cloak x-show="carrier === 'own'">
                        <label for="own_carrier_note" class="block text-sm font-medium text-slate-700">{{ __('orders.checkout.own_carrier_note') }}</label>
                        <textarea id="own_carrier_note" name="own_carrier_note" rows="2" class="{{ $input }}">{{ old('own_carrier_note') }}</textarea>
                        <p class="mt-1 text-xs text-slate-500">{{ __('orders.checkout.own_carrier_hint') }}</p>
                        @error('own_carrier_note')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>
                </section>

                <section class="space-y-3" aria-labelledby="payment-title">
                    <h2 id="payment-title" class="text-xl font-semibold text-brand-900">{{ __('orders.checkout.payment') }}</h2>
                    @foreach ($methods as $method)
                        <label class="flex items-start gap-3 rounded-lg p-3 ring-1 ring-slate-200" :class="isAllowed('{{ $method->value }}') ? 'cursor-pointer hover:bg-slate-50' : 'opacity-50'">
                            <input type="radio" name="payment_method" value="{{ $method->value }}" x-model="payment" :disabled="!isAllowed('{{ $method->value }}')" required class="mt-1 text-brand-700 focus:ring-brand-600">
                            <span>
                                <span class="block text-sm font-medium text-slate-900">{{ $method->label() }}</span>
                                @if ($method === \App\Enums\PaymentMethod::CashOnDelivery)
                                    <span class="block text-xs text-slate-500">{{ __('orders.checkout.cod_unavailable', ['limit' => $codLimit->format()]) }}</span>
                                @endif
                            </span>
                        </label>
                    @endforeach
                    @error('payment_method')<p class="text-xs text-red-600">{{ $message }}</p>@enderror
                </section>

                <div>
                    <label for="customer_note" class="block text-sm font-medium text-slate-700">{{ __('orders.checkout.note') }}</label>
                    <textarea id="customer_note" name="customer_note" rows="2" class="{{ $input }}">{{ old('customer_note') }}</textarea>
                </div>
            </div>

            <aside class="h-fit rounded-xl bg-slate-50 p-5 lg:sticky lg:top-6" aria-labelledby="summary-title">
                <h2 id="summary-title" class="text-lg font-semibold text-brand-900">{{ __('orders.checkout.summary') }}</h2>
                <ul class="mt-4 divide-y divide-slate-200 text-sm">
                    @foreach ($summary->lines as $line)
                        <li class="flex justify-between gap-3 py-2">
                            <span class="text-slate-700">{{ $line->quantity }} × {{ $line->product->name }}</span>
                            <span class="shrink-0 font-medium">{{ $line->quote->lineTtc }}</span>
                        </li>
                    @endforeach
                </ul>

                <dl class="mt-4 space-y-2 border-t border-slate-200 pt-4 text-sm">
                    <div class="flex justify-between"><dt class="text-slate-600">{{ __('orders.cart.total_before_shipping') }}</dt><dd>{{ $summary->itemsTtc() }}</dd></div>
                    <div class="flex justify-between">
                        <dt class="text-slate-600">{{ __('orders.checkout.shipping') }}</dt>
                        <dd>
                            <span x-show="loading" x-cloak>{{ __('orders.checkout.estimating') }}</span>
                            <span x-show="!loading && quote" x-cloak x-text="quote && quote.shipping_ht.formatted"></span>
                            <span x-show="!loading && !quote" class="text-slate-500">{{ __('orders.checkout.shipping_pending') }}</span>
                        </dd>
                    </div>
                    <div class="flex justify-between border-t border-slate-200 pt-2 text-base font-bold text-brand-900">
                        <dt>{{ __('orders.checkout.total') }}</dt>
                        <dd x-text="quote ? quote.total_ttc.formatted : @js($summary->itemsTtc()->format())">{{ $summary->itemsTtc() }}</dd>
                    </div>
                </dl>

                <p x-cloak x-show="error" x-text="error" class="mt-3 rounded-lg bg-red-50 px-3 py-2 text-xs text-red-700" role="alert"></p>

                <button type="submit" class="mt-5 w-full rounded-lg bg-brand-700 px-5 py-3 text-sm font-semibold text-white hover:bg-brand-800">{{ __('orders.checkout.place_order') }}</button>
            </aside>
        </form>
    </div>
</x-layouts.app>
