@props(['title'])

<div class="mx-auto flex max-w-md flex-col px-4 py-12 sm:py-16">
    <h1 class="text-2xl font-semibold tracking-tight text-brand-900">{{ $title }}</h1>

    @if (session('status'))
        <p class="mt-4 rounded-lg bg-emerald-50 px-4 py-3 text-sm text-emerald-800 ring-1 ring-emerald-200" role="status">{{ session('status') }}</p>
    @endif

    <div class="mt-6 rounded-xl bg-white p-6 shadow-xs ring-1 ring-slate-200">
        {{ $slot }}
    </div>

    @isset($footer)
        <p class="mt-6 text-center text-sm text-slate-600">{{ $footer }}</p>
    @endisset
</div>
