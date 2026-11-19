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
    <meta name="csrf-token" content="{{ csrf_token() }}">
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
    @if ($announcement->isVisible() && $announcement->dismissible)
        <script>try{if(localStorage.getItem('mitek_announce_{{ $announcementHash }}'))document.documentElement.classList.add('announce-dismissed')}catch(e){}</script>
        <style>.announce-dismissed [data-announcement]{display:none}</style>
    @endif
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex min-h-screen flex-col bg-white font-sans text-slate-800 antialiased" data-cart-count="{{ $cartCount }}" x-data="siteShell()" @scroll.window.passive="onScroll()">
    <x-layout.announcement :announcement="$announcement" :hash="$announcementHash" />
    <x-layout.topbar :site="$site" />
    <x-layout.header :site="$site" :cart-count="$cartCount" />
    <x-layout.nav :menu="$menu" :site="$site" />
    <x-layout.drawer :menu="$menu" :site="$site" :cart-count="$cartCount" />
    <x-layout.cart-drawer :menu="$menu" :cart-count="$cartCount" />

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
