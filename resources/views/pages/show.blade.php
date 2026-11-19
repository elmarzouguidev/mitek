@php
    $renderer = app(\App\Services\Content\ContentRenderer::class);
    $isMarketing = $page->key->isMarketing();
    $faqItems = $faqCategory?->items ?? collect();
    $metaDescription = $page->meta_description ?: \App\Support\Seo::description($renderer->replaceTokens($page->content));
@endphp

<x-layouts.app :title="$page->meta_title ?: $page->title" :description="$metaDescription">
    @if ($isMarketing)
        <section class="bg-brand-950 text-white">
            <div class="mx-auto max-w-7xl px-4 py-14 sm:px-6 sm:py-20 lg:px-8">
                <x-breadcrumb :items="[$page->title => null]" class="[&_a]:text-brand-200 [&_li]:text-brand-200 [&_li[aria-current]]:text-white" />
                <h1 class="mt-6 max-w-3xl text-4xl font-bold tracking-tight sm:text-5xl">{{ $page->title }}</h1>
            </div>
        </section>

        <div class="mx-auto max-w-3xl px-4 py-14 sm:px-6">
            <div class="prose-mitek text-lg text-slate-700">{{ $renderer->html($page->content) }}</div>
        </div>

        <section class="bg-brand-50">
            <div class="mx-auto flex max-w-7xl flex-col items-start justify-between gap-6 px-4 py-12 sm:px-6 md:flex-row md:items-center lg:px-8">
                <p class="text-xl font-semibold text-brand-900">{{ __('app.pages.cta') }}</p>
                <div class="flex flex-wrap gap-3">
                    <a href="{{ route('services.index') }}" class="rounded-lg bg-brand-700 px-5 py-3 text-sm font-semibold text-white transition hover:bg-brand-800 active:scale-[0.98]">{{ __('app.pages.cta_services') }}</a>
                    <a href="{{ route('contact') }}" class="rounded-lg px-5 py-3 text-sm font-semibold text-brand-700 ring-1 ring-inset ring-brand-200 transition hover:bg-white active:scale-[0.98]">{{ __('app.pages.cta_contact') }}</a>
                </div>
            </div>
        </section>
    @else
        <div class="mx-auto max-w-3xl px-4 py-10 sm:px-6">
            <x-breadcrumb :items="[$page->title => null]" />
            <h1 class="mt-5 text-3xl font-bold tracking-tight text-brand-900">{{ $page->title }}</h1>
            <div class="prose-mitek mt-6 text-slate-700">{{ $renderer->html($page->content) }}</div>

            <x-faq-list class="mt-12" :items="$faqItems" :heading="__('app.pages.faq')" :level="2" />
        </div>
    @endif
</x-layouts.app>
