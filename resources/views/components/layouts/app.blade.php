@props(['title' => null, 'description' => null])

@php
    $pageTitle = $title ? $title.' · '.$site->site_name : $site->site_name.' · '.__('app.home.title');
    $whatsappUrl = $site->whatsapp ? 'https://wa.me/'.$site->whatsapp : null;
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $pageTitle }}</title>
    @if ($description)
        <meta name="description" content="{{ $description }}">
    @endif
    <link rel="canonical" href="{{ url()->current() }}">
    <meta property="og:title" content="{{ $pageTitle }}">
    <meta property="og:site_name" content="{{ $site->site_name }}">
    <meta property="og:locale" content="fr_MA">
    <link rel="icon" href="{{ asset('favicon.ico') }}">
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
                @auth
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
            @foreach ($menu as $root)
                <a href="{{ route('categories.show', $root['slug']) }}" class="block rounded-lg px-3 py-2 hover:bg-slate-100">{{ $root['name'] }}</a>
            @endforeach
            @auth
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
        {{ $slot }}
    </main>

    <footer class="bg-brand-950 text-brand-200">
        <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
            <div class="flex flex-col gap-6 sm:flex-row sm:justify-between">
                <div>
                    <p class="text-lg font-semibold text-white">{{ $site->site_name }}</p>
                    <p class="mt-1 text-sm">{{ $site->legal_name }}</p>
                </div>
                <address class="space-y-1 text-sm not-italic">
                    <p>{{ $site->address }}</p>
                    <p><a href="mailto:{{ $site->email }}" class="hover:text-white">{{ $site->email }}</a></p>
                    @if ($site->phone)
                        <p><a href="tel:{{ preg_replace('/\s+/', '', $site->phone) }}" class="hover:text-white">{{ $site->phone }}</a></p>
                    @endif
                </address>
            </div>
            <p class="mt-8 border-t border-brand-900 pt-6 text-xs">&copy; {{ now()->year }} {{ $site->legal_name }}. {{ __('app.footer.rights') }}</p>
        </div>
    </footer>

    @if ($whatsappUrl)
        <a href="{{ $whatsappUrl }}" target="_blank" rel="noopener" aria-label="WhatsApp" class="fixed bottom-5 right-5 z-40 rounded-full bg-emerald-500 px-4 py-3 text-sm font-semibold text-white shadow-lg hover:bg-emerald-600">WhatsApp</a>
    @endif
</body>
</html>
