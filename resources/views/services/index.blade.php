<x-layouts.app :title="__('services.index.title')" :description="__('services.index.description')">
    <section class="bg-brand-950 text-white">
        <div class="mx-auto max-w-7xl px-4 py-16 sm:px-6 sm:py-24 lg:px-8">
            <p class="text-sm font-semibold uppercase tracking-wide text-brand-300">{{ __('services.index.kicker') }}</p>
            <h1 class="mt-3 max-w-3xl text-4xl font-bold tracking-tight sm:text-5xl">{{ __('services.index.heading') }}</h1>
            <p class="mt-5 max-w-2xl text-lg text-brand-100">{{ __('services.index.intro') }}</p>
            <a href="{{ route('contact') }}" class="mt-8 inline-flex rounded-lg bg-white px-6 py-3 text-sm font-semibold text-brand-900 transition hover:bg-brand-50 active:scale-[0.98]">{{ __('services.index.cta_button') }}</a>
        </div>
    </section>

    <section class="mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8" aria-label="{{ __('services.index.title') }}">
        <ul class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($services as $service)
                <li>
                    <a href="{{ route('services.show', $service->slug) }}" class="group flex h-full flex-col rounded-2xl bg-white p-6 ring-1 ring-slate-200 transition hover:-translate-y-0.5 hover:ring-brand-300">
                        <span class="flex size-11 items-center justify-center rounded-xl bg-brand-50 text-brand-700"><x-service-icon :name="$service->icon" class="size-6" /></span>
                        <h2 class="mt-4 text-lg font-semibold text-brand-900">{{ $service->title }}</h2>
                        <p class="mt-2 flex-1 text-sm text-slate-600">{{ $service->summary }}</p>
                        <span class="mt-4 text-sm font-semibold text-brand-700 group-hover:underline">{{ __('services.index.discover') }} →</span>
                    </a>
                </li>
            @endforeach
        </ul>
    </section>

    <section class="bg-brand-50">
        <div class="mx-auto flex max-w-7xl flex-col items-start justify-between gap-6 px-4 py-14 sm:px-6 md:flex-row md:items-center lg:px-8">
            <div>
                <h2 class="text-2xl font-semibold tracking-tight text-brand-900">{{ __('services.index.cta_title') }}</h2>
                <p class="mt-2 text-slate-600">{{ __('services.index.cta_text') }}</p>
            </div>
            <a href="{{ route('contact') }}" class="rounded-lg bg-brand-700 px-6 py-3 text-sm font-semibold text-white transition hover:bg-brand-800 active:scale-[0.98]">{{ __('services.index.cta_button') }}</a>
        </div>
    </section>
</x-layouts.app>
