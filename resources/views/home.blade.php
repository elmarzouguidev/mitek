<x-layouts.app :description="__('app.home.welcome')">
    <section class="bg-brand-50">
        <div class="mx-auto max-w-7xl px-4 py-16 sm:px-6 sm:py-24 lg:px-8">
            <h1 class="max-w-3xl text-4xl font-bold tracking-tight text-brand-900 sm:text-5xl">{{ __('app.home.tagline') }}</h1>
            <p class="mt-5 max-w-2xl text-lg text-slate-600">{{ __('app.home.welcome') }}</p>

            <ol class="mt-8 flex flex-wrap gap-3 text-sm font-medium text-brand-800">
                @foreach (__('app.home.steps') as $step)
                    <li class="flex items-center gap-2 rounded-full bg-white px-4 py-2 shadow-xs ring-1 ring-brand-100">
                        <span class="flex size-5 items-center justify-center rounded-full bg-brand-700 text-xs text-white">{{ $loop->iteration }}</span>
                        {{ $step }}
                    </li>
                @endforeach
            </ol>
        </div>
    </section>

    <section class="mx-auto max-w-7xl px-4 py-14 sm:px-6 lg:px-8">
        <h2 class="text-2xl font-semibold tracking-tight text-brand-900">{{ __('app.home.products_title') }}</h2>
        <div class="mt-6 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
            @foreach (__('app.home.categories') as $name => $summary)
                <article class="rounded-xl p-5 ring-1 ring-slate-200">
                    <h3 class="font-semibold text-brand-800">{{ $name }}</h3>
                    <p class="mt-2 text-sm text-slate-600">{{ $summary }}</p>
                </article>
            @endforeach
        </div>
    </section>

    <section class="bg-slate-50">
        <div class="mx-auto max-w-7xl px-4 py-14 sm:px-6 lg:px-8">
            <h2 class="text-2xl font-semibold tracking-tight text-brand-900">{{ __('app.home.why_title') }}</h2>
            <ul class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach (__('app.home.why') as $reason)
                    <li class="flex items-start gap-3 text-slate-700">
                        <svg class="mt-0.5 size-5 shrink-0 text-brand-600" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M16.7 5.3a1 1 0 0 1 0 1.4l-7.5 7.5a1 1 0 0 1-1.4 0L3.3 9.7a1 1 0 1 1 1.4-1.4l3.8 3.8 6.8-6.8a1 1 0 0 1 1.4 0Z" clip-rule="evenodd"/></svg>
                        {{ $reason }}
                    </li>
                @endforeach
            </ul>
        </div>
    </section>
</x-layouts.app>
