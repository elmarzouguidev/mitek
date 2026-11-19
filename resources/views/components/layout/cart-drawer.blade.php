@props(['menu', 'cartCount'])

<div
    x-data="cartDrawer({ summaryUrl: @js(route('cart.summary')), count: {{ $cartCount }}, errorMessage: @js(__('orders.drawer.error')) })"
    @cart:open.window="show($event.target)"
    @cart:add.window="add($event.detail.form, $event.detail.form.querySelector('button[type=submit]'))"
    @keydown.escape.window="open && hide()"
>
    <div x-cloak x-show="open" x-transition.opacity.duration.200ms class="fixed inset-0 z-50 bg-slate-900/50" @click="hide()" aria-hidden="true"></div>

    <div
        x-cloak
        x-show="open"
        x-ref="panel"
        x-transition:enter="transition duration-200 ease-out motion-reduce:transition-none"
        x-transition:enter-start="translate-x-full"
        x-transition:enter-end="translate-x-0"
        x-transition:leave="transition duration-150 ease-in motion-reduce:transition-none"
        x-transition:leave-start="translate-x-0"
        x-transition:leave-end="translate-x-full"
        role="dialog"
        aria-modal="true"
        aria-labelledby="cart-drawer-title"
        id="cart-drawer"
        class="fixed inset-y-0 right-0 z-50 flex w-full flex-col bg-white shadow-xl overscroll-contain sm:max-w-md"
    >
        <div class="flex items-center justify-between border-b border-slate-200 px-4 py-3">
            <p id="cart-drawer-title" class="text-lg font-semibold text-brand-900">
                {{ __('orders.drawer.title') }}
                <span x-show="cart.count > 0" class="text-sm font-normal text-slate-500" x-text="'(' + cart.count + ')'"></span>
            </p>
            <button type="button" x-ref="close" @click="hide()" class="rounded-lg p-2 text-slate-700 hover:bg-slate-100" aria-label="{{ __('orders.drawer.close') }}">
                <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 6l12 12M18 6 6 18"/></svg>
            </button>
        </div>

        <div class="px-4 pt-3 empty:hidden" role="status" aria-live="polite">
            <p x-show="notice" x-text="notice" class="rounded-lg bg-emerald-50 px-3 py-2 text-sm font-medium text-emerald-800 ring-1 ring-emerald-200"></p>
        </div>
        <p x-show="error" x-text="error" role="alert" class="mx-4 mt-3 rounded-lg bg-red-50 px-3 py-2 text-sm text-red-800 ring-1 ring-red-200"></p>
        <template x-for="message in cart.adjustments" :key="message">
            <p class="mx-4 mt-3 rounded-lg bg-amber-50 px-3 py-2 text-sm text-amber-900 ring-1 ring-amber-200" x-text="message"></p>
        </template>

        <div class="flex-1 overflow-y-auto overscroll-contain px-4 py-3">
            <p x-show="loading" class="py-8 text-center text-sm text-slate-500">{{ __('orders.drawer.loading') }}</p>

            <ul x-show="lines.length > 0" class="divide-y divide-slate-100">
                <template x-for="line in lines" :key="line.slug">
                    <li class="flex gap-3 py-3 transition-opacity" :class="line.pending ? 'opacity-60' : ''">
                        <a :href="line.url" class="size-16 shrink-0 overflow-hidden rounded-lg bg-slate-50 outline outline-1 -outline-offset-1 outline-black/10">
                            <img x-show="line.image" :src="line.image" alt="" class="size-full object-contain" loading="lazy">
                        </a>
                        <div class="min-w-0 flex-1">
                            <a :href="line.url" class="block text-sm font-semibold text-slate-900 hover:text-brand-700" x-text="line.name"></a>
                            <p class="text-xs text-slate-500">{{ __('catalog.reference') }} <span x-text="line.reference"></span></p>
                            <p class="mt-0.5 text-xs text-slate-600">
                                <span x-text="line.unit_ttc"></span> {{ __('catalog.ttc') }}
                                <span x-show="line.tier_applied" x-text="line.tier_label" class="ml-1 font-medium text-emerald-700"></span>
                            </p>
                            <div class="mt-2 flex items-center justify-between gap-2">
                                <div class="flex items-center rounded-lg ring-1 ring-inset ring-slate-300">
                                    <button type="button" @click="setQuantity(line, line.quantity - 1)" class="size-9 rounded-l-lg text-lg leading-none text-slate-700 hover:bg-slate-100" :aria-label="@js(__('orders.drawer.decrease')).replace(':name', line.name)">−</button>
                                    <input type="number" min="1" :max="line.max" :value="line.quantity" @change="setQuantity(line, $event.target.value); $event.target.value = line.quantity" :aria-label="@js(__('orders.drawer.quantity_of')).replace(':name', line.name)" class="h-9 w-12 [appearance:textfield] border-0 p-0 text-center text-sm focus:ring-2 focus:ring-inset focus:ring-brand-600 [&::-webkit-inner-spin-button]:appearance-none [&::-webkit-outer-spin-button]:appearance-none">
                                    <button type="button" @click="setQuantity(line, line.quantity + 1)" :disabled="line.quantity >= line.max" class="size-9 rounded-r-lg text-lg leading-none text-slate-700 hover:bg-slate-100 disabled:cursor-not-allowed disabled:opacity-40" :aria-label="@js(__('orders.drawer.increase')).replace(':name', line.name)">+</button>
                                </div>
                                <p class="text-sm font-semibold text-brand-900" x-text="line.line_ttc"></p>
                            </div>
                            <button type="button" @click="remove(line)" class="-ml-1 mt-0.5 px-1 py-1.5 text-xs font-medium text-red-600 hover:underline" :aria-label="@js(__('orders.drawer.remove')).replace(':name', line.name)">{{ __('orders.cart.remove') }}</button>
                        </div>
                    </li>
                </template>
            </ul>

            <div x-show="isEmpty" class="py-8 text-center">
                <p class="font-medium text-slate-800">{{ __('orders.drawer.empty') }}</p>
                <p class="mt-1 text-sm text-slate-500">{{ __('orders.drawer.empty_hint') }}</p>
                @if ($menu !== [])
                    <ul class="mt-5 space-y-2 text-sm">
                        @foreach ($menu as $root)
                            <li><a href="{{ route('categories.show', $root['slug']) }}" class="block rounded-lg px-4 py-2.5 font-medium text-brand-700 ring-1 ring-inset ring-brand-200 hover:bg-brand-50">{{ $root['short_name'] }}</a></li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>

        <div x-show="lines.length > 0" class="space-y-3 border-t border-slate-200 bg-slate-50 px-4 py-4">
            <div class="flex items-baseline justify-between">
                <p class="text-sm font-medium text-slate-700">{{ __('orders.drawer.subtotal_ttc') }}</p>
                <p class="text-lg font-bold text-brand-900" x-text="cart.subtotal_ttc"></p>
            </div>
            <p class="text-xs text-slate-500">{{ __('orders.drawer.shipping_note') }}</p>
            <p x-show="cart.cod" x-text="cart.cod?.message" class="rounded-lg px-3 py-2 text-xs" :class="cart.cod?.eligible ? 'bg-emerald-50 text-emerald-800' : 'bg-amber-50 text-amber-900'"></p>
            <div class="grid grid-cols-2 gap-2">
                <a :href="cart.urls.cart" class="rounded-lg px-4 py-2.5 text-center text-sm font-semibold text-brand-700 ring-1 ring-inset ring-brand-200 hover:bg-brand-50">{{ __('orders.drawer.view_cart') }}</a>
                <a :href="cart.urls.checkout" class="rounded-lg bg-brand-700 px-4 py-2.5 text-center text-sm font-semibold text-white transition hover:bg-brand-800 active:scale-[0.98]">{{ __('orders.drawer.checkout') }}</a>
            </div>
            <a x-show="cart.urls.quote" :href="cart.urls.quote" class="block text-center text-sm font-medium text-brand-700 hover:underline">{{ __('orders.drawer.quote') }}</a>
        </div>
    </div>
</div>
