@props(['status', 'title', 'message'])

@php
    // The error page must render even when the database is down: the cached menu is optional.
    try {
        $menu = app(\App\Services\Core\Product\CatalogMenu::class)->tree();
    } catch (\Throwable) {
        $menu = [];
    }
@endphp
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }} · {{ config('app.name') }}</title>
    <meta name="robots" content="noindex,nofollow">
    @vite(['resources/css/app.css'])
</head>
<body class="flex min-h-screen flex-col bg-white font-sans text-slate-800 antialiased">
    <header class="border-b border-slate-200">
        <div class="mx-auto flex h-16 max-w-7xl items-center px-4 sm:px-6 lg:px-8">
            <a href="{{ url('/') }}" aria-label="{{ config('app.name') }}"><img src="{{ asset('images/logo-mitek.png') }}" alt="{{ config('app.name') }}" class="h-10 w-auto"></a>
        </div>
    </header>

    <main class="flex-1">
        <div class="mx-auto max-w-3xl px-4 py-16 text-center sm:px-6 sm:py-24">
            <p class="text-sm font-semibold uppercase tracking-wide text-brand-600">{{ __('errors.code', ['status' => $status]) }}</p>
            <h1 class="mt-3 text-4xl font-bold tracking-tight text-brand-900">{{ $title }}</h1>
            <p class="mt-4 text-lg text-slate-600">{{ $message }}</p>

            <form method="GET" action="{{ url('/recherche') }}" role="search" class="mx-auto mt-8 flex max-w-md gap-2">
                <label for="error-search" class="sr-only">{{ __('catalog.search.title') }}</label>
                <input id="error-search" type="search" name="q" maxlength="100" placeholder="{{ __('errors.search_placeholder') }}" class="block w-full rounded-lg border-0 px-4 py-2.5 text-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-brand-600">
                <button type="submit" class="shrink-0 rounded-lg bg-brand-700 px-5 py-2.5 text-sm font-semibold text-white hover:bg-brand-800">{{ __('catalog.search.button') }}</button>
            </form>

            <a href="{{ url('/') }}" class="mt-6 inline-block rounded-lg px-5 py-2.5 text-sm font-semibold text-brand-700 ring-1 ring-inset ring-brand-200 hover:bg-brand-50">{{ __('errors.home') }}</a>

            @if ($menu !== [])
                <nav class="mt-12 border-t border-slate-200 pt-8" aria-label="{{ __('errors.categories') }}">
                    <h2 class="text-sm font-semibold text-slate-900">{{ __('errors.categories') }}</h2>
                    <ul class="mt-4 flex flex-wrap justify-center gap-2">
                        @foreach ($menu as $root)
                            <li><a href="{{ url('/categories/'.$root['slug']) }}" class="inline-block rounded-full bg-slate-100 px-3 py-1.5 text-sm text-slate-700 hover:bg-brand-100 hover:text-brand-800">{{ $root['name'] }}</a></li>
                        @endforeach
                    </ul>
                </nav>
            @endif
        </div>
    </main>

    <footer class="bg-brand-950 py-6 text-center text-xs text-brand-200">&copy; {{ now()->year }} {{ config('app.name') }}</footer>
</body>
</html>
