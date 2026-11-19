@props(['title', 'active'])

@php
    $items = [
        'overview' => ['account.index', __('account.nav.overview')],
        'orders' => ['account.orders', __('account.nav.orders')],
        'quotes' => ['account.quotes', __('account.nav.quotes')],
        'addresses' => ['account.addresses', __('account.nav.addresses')],
        'profile' => ['account.profile', __('account.nav.profile')],
    ];
@endphp

<x-layouts.app :title="$title">
    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <h1 class="text-3xl font-bold tracking-tight text-brand-900">{{ __('account.title') }}</h1>

        <div class="mt-6 grid gap-8 lg:grid-cols-[14rem_1fr]">
            <nav aria-label="{{ __('account.title') }}" class="flex gap-1 overflow-x-auto lg:flex-col">
                @foreach ($items as $key => [$route, $label])
                    <a href="{{ route($route) }}" @if ($active === $key) aria-current="page" @endif class="whitespace-nowrap rounded-lg px-3 py-2 text-sm font-medium {{ $active === $key ? 'bg-brand-50 text-brand-800' : 'text-slate-600 hover:bg-slate-100' }}">{{ $label }}</a>
                @endforeach
            </nav>

            <div>{{ $slot }}</div>
        </div>
    </div>
</x-layouts.app>
