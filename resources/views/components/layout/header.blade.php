@props(['site', 'cartCount'])

<header class="sticky top-0 z-40 border-b border-slate-200 bg-white/95 backdrop-blur">
    <div class="mx-auto flex max-w-7xl flex-wrap items-center gap-x-4 gap-y-2 px-4 transition-[padding] duration-200 sm:px-6 lg:flex-nowrap lg:px-8" :class="compact ? 'py-2' : 'py-3'">
        <a href="{{ route('home') }}" class="order-1 flex shrink-0 items-center" aria-label="{{ $site->site_name }}">
            <img src="{{ asset('images/logo-mitek.png') }}" alt="{{ $site->site_name }}" width="115" height="40" class="w-auto transition-[height] duration-200" :class="compact ? 'h-7 lg:h-9' : 'h-10'">
        </a>

        <div class="order-3 w-full lg:order-2 lg:mx-auto lg:w-auto lg:max-w-2xl lg:flex-1">
            <x-search-box />
        </div>

        <div class="order-2 ml-auto flex items-center gap-1 lg:order-3 lg:ml-0">
            @auth
                @if (auth()->user()->isAdmin())
                    <a href="{{ route('admin.dashboard') }}" class="hidden rounded-lg px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100 xl:inline-block">{{ __('app.nav.admin') }}</a>
                @endif
                <a href="{{ route('account.index') }}" class="flex items-center gap-2 rounded-lg px-2 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100 sm:px-3">
                    <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6.75a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.5 19.5a7.5 7.5 0 0 1 15 0"/></svg>
                    <span class="sr-only xl:not-sr-only">{{ __('app.nav.account') }}</span>
                </a>
            @else
                <a href="{{ route('login') }}" class="flex items-center gap-2 rounded-lg px-2 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100 sm:px-3">
                    <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6.75a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.5 19.5a7.5 7.5 0 0 1 15 0"/></svg>
                    <span class="sr-only xl:not-sr-only">{{ __('app.nav.login') }}</span>
                </a>
            @endauth

            <a href="{{ route('cart.show') }}" class="relative flex items-center gap-2 rounded-lg px-2 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100 sm:px-3">
                <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.39a1.5 1.5 0 0 1 1.45 1.1l.4 1.4m0 0 1.6 6.4a1.5 1.5 0 0 0 1.46 1.1h8.6a1.5 1.5 0 0 0 1.46-1.1l1.28-5.1a1.5 1.5 0 0 0-1.46-1.9H5.49ZM9 19.5a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Zm8.25 0a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Z"/></svg>
                <span class="sr-only xl:not-sr-only">{{ __('orders.cart.title') }}</span>
                @if ($cartCount > 0)
                    <span class="inline-flex min-w-5 items-center justify-center rounded-full bg-brand-700 px-1.5 text-xs font-semibold text-white xl:ml-0">{{ $cartCount }}</span>
                @endif
            </a>

            <button type="button" x-ref="drawerTrigger" @click="openDrawer()" class="rounded-lg p-2 text-slate-700 hover:bg-slate-100 lg:hidden" aria-label="{{ __('app.nav.menu') }}" aria-haspopup="dialog" :aria-expanded="drawerOpen ? 'true' : 'false'" aria-controls="mobile-drawer">
                <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5M3.75 17.25h16.5"/></svg>
            </button>
        </div>
    </div>
</header>
