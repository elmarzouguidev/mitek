@props(['menu', 'site', 'cartCount'])

@php($whatsappUrl = whatsapp_url())

<div id="mobile-drawer" x-cloak x-show="drawerOpen" role="dialog" aria-modal="true" aria-label="{{ __('app.nav.menu') }}" class="fixed inset-0 z-50 overflow-y-auto overscroll-contain bg-white lg:hidden" @keydown.escape.window="closeDrawer()">
    <div class="flex items-center justify-between border-b border-slate-200 px-4 py-3">
        <a href="{{ route('home') }}" aria-label="{{ $site->site_name }}"><img src="{{ asset('images/logo-mitek.png') }}" alt="{{ $site->site_name }}" width="92" height="32" class="h-8 w-auto"></a>
        <button type="button" x-ref="drawerClose" @click="closeDrawer()" class="rounded-lg p-2 text-slate-700 hover:bg-slate-100" aria-label="{{ __('app.nav.close_menu') }}">
            <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 6l12 12M18 6 6 18"/></svg>
        </button>
    </div>

    <nav class="px-4 py-4" aria-label="{{ __('app.nav.menu') }}">
        <ul class="divide-y divide-slate-100">
            <li><a href="{{ route('home') }}" class="block py-3 text-base font-medium text-slate-900">{{ __('app.nav.home') }}</a></li>

            @foreach ($menu as $root)
                <li>
                    <div class="flex items-center justify-between">
                        <a href="{{ route('categories.show', $root['slug']) }}" class="flex-1 py-3 text-base font-medium text-slate-900">{{ $root['short_name'] }} <span class="text-sm font-normal text-slate-400">({{ $root['count'] }})</span></a>
                        @if ($root['children'] !== [])
                            <button type="button" @click="toggleFamily('{{ $root['slug'] }}')" class="rounded-lg p-3 text-slate-500 hover:bg-slate-100" :aria-expanded="family === '{{ $root['slug'] }}' ? 'true' : 'false'" aria-controls="drawer-{{ $root['slug'] }}" aria-label="{{ __('app.nav.subcategories', ['name' => $root['short_name']]) }}">
                                <svg class="size-5 transition" :class="family === '{{ $root['slug'] }}' ? 'rotate-180' : ''" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M5.2 7.2a.75.75 0 0 1 1.06 0L10 10.94l3.74-3.74a.75.75 0 1 1 1.06 1.06l-4.27 4.27a.75.75 0 0 1-1.06 0L5.2 8.26a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd"/></svg>
                            </button>
                        @endif
                    </div>
                    @if ($root['children'] !== [])
                        <ul id="drawer-{{ $root['slug'] }}" x-cloak x-show="family === '{{ $root['slug'] }}'" class="mb-3 space-y-1 border-l-2 border-brand-100 pl-4">
                            @foreach ($root['children'] as $child)
                                <li><a href="{{ route('categories.show', $child['slug']) }}" class="flex justify-between py-2 text-sm text-slate-600"><span>{{ $child['name'] }}</span><span class="text-slate-400">{{ $child['count'] }}</span></a></li>
                            @endforeach
                        </ul>
                    @endif
                </li>
            @endforeach

            <li><a href="{{ route('services.index') }}" class="block py-3 text-base font-semibold text-brand-700">{{ __('app.nav.services') }}</a></li>
            @if (nav_feature('pro'))
                <li><a href="{{ route('pro.show') }}" class="block py-3 text-base font-medium text-slate-900">{{ __('app.nav.pro') }}</a></li>
            @endif
            <li><a href="{{ route('contact') }}" class="block py-3 text-base font-medium text-slate-900">{{ __('app.nav.contact') }}</a></li>
            @if (nav_feature('callback'))
                <li><a href="{{ route('callback.create') }}" class="block py-3 text-base font-medium text-slate-900">{{ __('app.topbar.callback') }}</a></li>
            @endif
            <li><a href="{{ route('cart.show') }}" class="block py-3 text-base font-medium text-slate-900">{{ __('orders.cart.title') }}@if ($cartCount > 0) ({{ $cartCount }})@endif</a></li>
            @auth
                <li><a href="{{ route('account.index') }}" class="block py-3 text-base font-medium text-slate-900">{{ __('app.nav.account') }}</a></li>
                @if (auth()->user()->isAdmin())
                    <li><a href="{{ route('admin.dashboard') }}" class="block py-3 text-base font-medium text-slate-900">{{ __('app.nav.admin') }}</a></li>
                @endif
                <li>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="block w-full py-3 text-left text-base font-medium text-slate-900">{{ __('app.nav.logout') }}</button>
                    </form>
                </li>
            @else
                <li><a href="{{ route('login') }}" class="block py-3 text-base font-medium text-slate-900">{{ __('app.nav.login') }}</a></li>
                <li><a href="{{ route('register') }}" class="block py-3 text-base font-medium text-slate-900">{{ __('app.nav.register') }}</a></li>
            @endauth
        </ul>

        @if (nav_feature('quote'))
            <a href="{{ route('quotes.standalone') }}" class="mt-4 block rounded-lg bg-brand-700 px-5 py-3 text-center text-sm font-semibold text-white">{{ __('app.nav.quote') }}</a>
        @endif
        @if ($whatsappUrl)
            <a href="{{ $whatsappUrl }}" target="_blank" rel="noopener noreferrer" class="mt-3 block rounded-lg px-5 py-3 text-center text-sm font-semibold text-emerald-700 ring-1 ring-inset ring-emerald-200">WhatsApp</a>
        @endif
        <p class="mt-6 text-center text-xs text-slate-500">{{ $site->email }}</p>
    </nav>
</div>
