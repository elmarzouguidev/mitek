<x-layouts.app :title="__('orders.cart.title')">
    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <h1 class="text-3xl font-bold tracking-tight text-brand-900">{{ __('orders.cart.title') }}</h1>

        @foreach ($summary->adjustments as $message)
            <p class="mt-4 rounded-lg bg-amber-50 px-4 py-3 text-sm text-amber-900 ring-1 ring-amber-200" role="status">{{ $message }}</p>
        @endforeach

        @if ($summary->isEmpty())
            <div class="mt-8 rounded-xl bg-slate-50 p-10 text-center">
                <p class="text-slate-600">{{ __('orders.cart.empty') }}</p>
                <a href="{{ route('home') }}" class="mt-4 inline-block rounded-lg bg-brand-700 px-5 py-2.5 text-sm font-semibold text-white hover:bg-brand-800">{{ __('orders.cart.continue') }}</a>
            </div>
        @else
            <div class="mt-8 grid gap-8 lg:grid-cols-[1fr_22rem]">
                <ul class="divide-y divide-slate-200 rounded-xl ring-1 ring-slate-200">
                    @foreach ($summary->lines as $line)
                        @php($product = $line->product)
                        <li class="flex gap-4 p-4">
                            <a href="{{ route('products.show', $product->slug) }}" class="size-20 shrink-0 overflow-hidden rounded-lg bg-slate-50">
                                <x-product-image :product="$product" conversion="thumb" class="size-full object-contain" />
                            </a>
                            <div class="min-w-0 flex-1">
                                <a href="{{ route('products.show', $product->slug) }}" class="font-semibold text-slate-900 hover:text-brand-700">{{ $product->name }}</a>
                                <p class="text-xs text-slate-500">{{ __('catalog.reference') }} {{ $product->reference }}</p>
                                <p class="mt-1 text-sm text-slate-600">
                                    {{ $line->quote->unitTtc }} {{ __('catalog.ttc') }}
                                    @if ($line->quote->tierApplied)
                                        <span class="ml-1 text-xs font-medium text-emerald-700">{{ __('orders.cart.tier_applied') }}</span>
                                    @endif
                                </p>

                                <div class="mt-3 flex flex-wrap items-center gap-3">
                                    <form method="POST" action="{{ route('cart.update', $product->slug) }}" class="flex items-center gap-2">
                                        @csrf
                                        @method('PATCH')
                                        <label for="qty-{{ $product->id }}" class="sr-only">{{ __('orders.cart.quantity') }}</label>
                                        <input id="qty-{{ $product->id }}" name="quantite" type="number" min="0" max="{{ $product->stock_quantity }}" value="{{ $line->quantity }}" class="w-20 rounded-lg border-0 px-3 py-1.5 text-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-brand-600">
                                        <button type="submit" class="rounded-lg px-3 py-1.5 text-sm font-medium text-brand-700 ring-1 ring-inset ring-brand-200 hover:bg-brand-50">{{ __('orders.cart.update') }}</button>
                                    </form>
                                    <form method="POST" action="{{ route('cart.destroy', $product->slug) }}">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-sm font-medium text-red-600 hover:underline">{{ __('orders.cart.remove') }}</button>
                                    </form>
                                </div>
                            </div>
                            <p class="shrink-0 text-right font-semibold text-brand-900">{{ $line->quote->lineTtc }}</p>
                        </li>
                    @endforeach
                </ul>

                <aside class="h-fit rounded-xl bg-slate-50 p-5">
                    <dl class="space-y-2 text-sm">
                        <div class="flex justify-between"><dt class="text-slate-600">{{ __('orders.cart.subtotal_ht') }}</dt><dd>{{ $summary->itemsHt() }}</dd></div>
                        <div class="flex justify-between"><dt class="text-slate-600">{{ __('orders.cart.vat') }}</dt><dd>{{ $summary->itemsVat() }}</dd></div>
                        <div class="flex justify-between border-t border-slate-200 pt-2 text-base font-bold text-brand-900"><dt>{{ __('orders.cart.total_before_shipping') }}</dt><dd>{{ $summary->itemsTtc() }}</dd></div>
                    </dl>
                    <p class="mt-3 text-xs text-slate-500">{{ __('orders.cart.shipping_note') }}</p>
                    <a href="{{ route('checkout.show') }}" class="mt-5 block rounded-lg bg-brand-700 px-5 py-3 text-center text-sm font-semibold text-white hover:bg-brand-800">{{ __('orders.cart.checkout') }}</a>
                    <a href="{{ route('quotes.create') }}" class="mt-3 block rounded-lg px-5 py-3 text-center text-sm font-semibold text-brand-700 ring-1 ring-inset ring-brand-200 hover:bg-brand-50">{{ __('quotes.cart_button') }}</a>
                    <a href="{{ route('home') }}" class="mt-3 block text-center text-sm text-brand-700 hover:underline">{{ __('orders.cart.continue') }}</a>
                </aside>
            </div>
        @endif
    </div>
</x-layouts.app>
