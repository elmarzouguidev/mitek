@props(['title' => null, 'description' => null, 'canonical' => null, 'robots' => null, 'image' => null, 'ogType' => 'website', 'schemas' => []])

@php
    $pageTitle = $title ? $title.' · '.$site->site_name : $site->site_name.' · '.__('app.home.title');
    $whatsappUrl = $site->whatsapp ? 'https://wa.me/'.$site->whatsapp : null;
    $metaDescription = \App\Support\Seo::limit($description ?: __('app.home.welcome'));
    $canonicalUrl = $canonical ?: url()->current();
    $shareImage = $image ?: asset('images/share-default.png');
    $structured = app(\App\Services\Seo\StructuredData::class);
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $pageTitle }}</title>
    <meta name="description" content="{{ $metaDescription }}">
    <meta name="robots" content="{{ $robots ?: 'index,follow' }}">
    <link rel="canonical" href="{{ $canonicalUrl }}">
    <meta property="og:type" content="{{ $ogType }}">
    <meta property="og:title" content="{{ $pageTitle }}">
    <meta property="og:description" content="{{ $metaDescription }}">
    <meta property="og:url" content="{{ $canonicalUrl }}">
    <meta property="og:image" content="{{ $shareImage }}">
    <meta property="og:site_name" content="{{ $site->site_name }}">
    <meta property="og:locale" content="fr_MA">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $pageTitle }}">
    <meta name="twitter:description" content="{{ $metaDescription }}">
    <meta name="twitter:image" content="{{ $shareImage }}">
    <link rel="icon" href="{{ asset('favicon.ico') }}">
    <script type="application/ld+json">{!! \App\Support\Seo::json($structured->organization()) !!}</script>
    @foreach ($schemas as $schema)
        <script type="application/ld+json">{!! \App\Support\Seo::json($schema) !!}</script>
    @endforeach
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex min-h-screen flex-col bg-white font-sans text-slate-800 antialiased">
    <header class="border-b border-slate-200 bg-white" x-data="{ open: false }">
        <div class="mx-auto flex h-16 max-w-7xl items-center justify-between px-4 sm:px-6 lg:px-8">
            <a href="{{ route('home') }}" class="flex items-center" aria-label="{{ $site->site_name }}">
                <img src="{{ asset('images/logo-mitek.png') }}" alt="{{ $site->site_name }}" class="h-10 w-auto">
            </a>

            <nav class="hidden items-center gap-6 text-sm font-medium md:flex" aria-label="Navigation principale">
                <a href="{{ route('home') }}" class="text-slate-700 hover:text-brand-700">{{ __('app.nav.home') }}</a>
                <a href="{{ route('services.index') }}" class="text-slate-700 hover:text-brand-700">{{ __('app.nav.services') }}</a>
                <a href="{{ route('contact') }}" class="text-slate-700 hover:text-brand-700">{{ __('app.nav.contact') }}</a>
                <a href="{{ route('cart.show') }}" class="relative text-slate-700 hover:text-brand-700">
                    {{ __('orders.cart.title') }}
                    @if ($cartCount > 0)
                        <span class="ml-1 inline-flex min-w-5 items-center justify-center rounded-full bg-brand-700 px-1.5 text-xs font-semibold text-white">{{ $cartCount }}</span>
                    @endif
                </a>
                @auth
                    <a href="{{ route('account.index') }}" class="text-slate-700 hover:text-brand-700">{{ __('app.nav.account') }}</a>
                    @if (auth()->user()->isAdmin())
                        <a href="{{ route('admin.dashboard') }}" class="text-slate-700 hover:text-brand-700">{{ __('app.nav.admin') }}</a>
                    @endif
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="text-slate-700 hover:text-brand-700">{{ __('app.nav.logout') }}</button>
                    </form>
                @else
                    <a href="{{ route('login') }}" class="text-slate-700 hover:text-brand-700">{{ __('app.nav.login') }}</a>
                    <a href="{{ route('register') }}" class="rounded-lg bg-brand-700 px-4 py-2 text-white hover:bg-brand-800">{{ __('app.nav.register') }}</a>
                @endauth
            </nav>

            <button type="button" class="rounded-lg p-2 text-slate-700 hover:bg-slate-100 md:hidden" aria-label="Menu" @click="open = !open" :aria-expanded="open">
                <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5M3.75 17.25h16.5" /></svg>
            </button>
        </div>

        <div class="border-t border-slate-100 bg-slate-50">
            <div class="mx-auto max-w-7xl px-4 py-3 sm:px-6 lg:px-8">
                <x-search-box />
            </div>
        </div>

        @if ($menu !== [])
            <nav class="hidden border-t border-slate-100 md:block" aria-label="Catégories">
                <ul class="mx-auto flex max-w-7xl flex-wrap gap-x-6 px-4 sm:px-6 lg:px-8">
                    @foreach ($menu as $root)
                        <li class="relative" x-data="{ show: false }" @mouseenter="show = true" @mouseleave="show = false" @keydown.escape="show = false">
                            <a href="{{ route('categories.show', $root['slug']) }}" class="block py-2.5 text-sm font-medium text-slate-700 hover:text-brand-700" @focus="show = true">{{ $root['name'] }}</a>
                            @if ($root['children'] !== [])
                                <ul x-cloak x-show="show" class="absolute left-0 top-full z-30 w-64 rounded-b-lg bg-white py-2 shadow-lg ring-1 ring-slate-200">
                                    @foreach ($root['children'] as $child)
                                        <li><a href="{{ route('categories.show', $child['slug']) }}" class="block px-4 py-2 text-sm text-slate-700 hover:bg-brand-50 hover:text-brand-800">{{ $child['name'] }}</a></li>
                                    @endforeach
                                </ul>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </nav>
        @endif

        <nav x-cloak x-show="open" class="space-y-1 border-t border-slate-200 px-4 py-3 text-sm font-medium md:hidden" aria-label="Navigation mobile">
            <a href="{{ route('home') }}" class="block rounded-lg px-3 py-2 hover:bg-slate-100">{{ __('app.nav.home') }}</a>
            <a href="{{ route('services.index') }}" class="block rounded-lg px-3 py-2 hover:bg-slate-100">{{ __('app.nav.services') }}</a>
            <a href="{{ route('contact') }}" class="block rounded-lg px-3 py-2 hover:bg-slate-100">{{ __('app.nav.contact') }}</a>
            <a href="{{ route('cart.show') }}" class="block rounded-lg px-3 py-2 hover:bg-slate-100">{{ __('orders.cart.title') }}@if ($cartCount > 0) ({{ $cartCount }})@endif</a>
            @foreach ($menu as $root)
                <a href="{{ route('categories.show', $root['slug']) }}" class="block rounded-lg px-3 py-2 hover:bg-slate-100">{{ $root['name'] }}</a>
            @endforeach
            @auth
                <a href="{{ route('account.index') }}" class="block rounded-lg px-3 py-2 hover:bg-slate-100">{{ __('app.nav.account') }}</a>
                @if (auth()->user()->isAdmin())
                    <a href="{{ route('admin.dashboard') }}" class="block rounded-lg px-3 py-2 hover:bg-slate-100">{{ __('app.nav.admin') }}</a>
                @endif
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="block w-full rounded-lg px-3 py-2 text-left hover:bg-slate-100">{{ __('app.nav.logout') }}</button>
                </form>
            @else
                <a href="{{ route('login') }}" class="block rounded-lg px-3 py-2 hover:bg-slate-100">{{ __('app.nav.login') }}</a>
                <a href="{{ route('register') }}" class="block rounded-lg px-3 py-2 hover:bg-slate-100">{{ __('app.nav.register') }}</a>
            @endauth
        </nav>
    </header>

    <main class="flex-1">
        @if (session('success') || session('error') || $errors->hasAny(['cart', 'quantite', 'payment']))
            <div class="mx-auto max-w-7xl px-4 pt-6 sm:px-6 lg:px-8" role="status">
                @if (session('success'))
                    <p class="rounded-lg bg-emerald-50 px-4 py-3 text-sm text-emerald-800 ring-1 ring-emerald-200">{{ session('success') }}</p>
                @endif
                @if (session('error'))
                    <p class="rounded-lg bg-red-50 px-4 py-3 text-sm text-red-800 ring-1 ring-red-200">{{ session('error') }}</p>
                @endif
                @foreach (['cart', 'quantite', 'payment'] as $key)
                    @error($key)
                        <p class="mt-2 rounded-lg bg-red-50 px-4 py-3 text-sm text-red-800 ring-1 ring-red-200">{{ $message }}</p>
                    @enderror
                @endforeach
            </div>
        @endif

        {{ $slot }}
    </main>

    <footer class="bg-brand-950 text-brand-200">
        <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
            <div class="grid gap-10 sm:grid-cols-2 lg:grid-cols-4">
                <div>
                    <p class="text-lg font-semibold text-white">{{ $site->site_name }}</p>
                    <p class="mt-1 text-sm">{{ $site->legal_name }}</p>
                    <address class="mt-4 space-y-1 text-sm not-italic">
                        <p>{{ $site->address }}</p>
                        <p><a href="mailto:{{ $site->email }}" class="hover:text-white">{{ $site->email }}</a></p>
                        @if ($site->phone)
                            <p><a href="tel:{{ preg_replace('/\s+/', '', $site->phone) }}" class="hover:text-white">{{ $site->phone }}</a></p>
                        @endif
                    </address>
                </div>
                <nav aria-label="{{ __('app.footer.company') }}">
                    <p class="text-sm font-semibold text-white">{{ __('app.footer.company') }}</p>
                    <ul class="mt-3 space-y-2 text-sm">
                        <li><a href="{{ route('pages.about') }}" class="hover:text-white">{{ __('app.footer.about') }}</a></li>
                        <li><a href="{{ route('pages.why-mitek') }}" class="hover:text-white">{{ __('app.footer.why') }}</a></li>
                        <li><a href="{{ route('services.index') }}" class="hover:text-white">{{ __('app.footer.services') }}</a></li>
                        <li><a href="{{ route('contact') }}" class="hover:text-white">{{ __('app.footer.contact') }}</a></li>
                    </ul>
                </nav>
                <nav aria-label="{{ __('app.footer.help') }}">
                    <p class="text-sm font-semibold text-white">{{ __('app.footer.help') }}</p>
                    <ul class="mt-3 space-y-2 text-sm">
                        <li><a href="{{ route('pages.payment') }}" class="hover:text-white">{{ __('app.footer.payment') }}</a></li>
                        <li><a href="{{ route('pages.delivery') }}" class="hover:text-white">{{ __('app.footer.delivery') }}</a></li>
                        <li><a href="{{ route('faq') }}" class="hover:text-white">{{ __('app.footer.faq') }}</a></li>
                    </ul>
                </nav>
                <nav aria-label="{{ __('app.footer.legal') }}">
                    <p class="text-sm font-semibold text-white">{{ __('app.footer.legal') }}</p>
                    <ul class="mt-3 space-y-2 text-sm">
                        <li><a href="{{ route('pages.legal-notice') }}" class="hover:text-white">{{ __('app.footer.legal_notice') }}</a></li>
                        <li><a href="{{ route('pages.terms') }}" class="hover:text-white">{{ __('app.footer.terms') }}</a></li>
                        <li><a href="{{ route('pages.privacy') }}" class="hover:text-white">{{ __('app.footer.privacy') }}</a></li>
                    </ul>
                </nav>
            </div>
            <p class="mt-10 border-t border-brand-900 pt-6 text-xs">&copy; {{ now()->year }} {{ $site->legal_name }}. {{ __('app.footer.rights') }}</p>
        </div>
    </footer>

    @if ($whatsappUrl)
        <a href="{{ $whatsappUrl }}" target="_blank" rel="noopener" aria-label="WhatsApp" class="fixed bottom-5 right-5 z-40 rounded-full bg-emerald-500 px-4 py-3 text-sm font-semibold text-white shadow-lg hover:bg-emerald-600">WhatsApp</a>
    @endif
</body>
</html>
