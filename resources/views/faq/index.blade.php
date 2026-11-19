@php
    $renderer = app(\App\Services\Content\ContentRenderer::class);
    $allItems = $categories->flatMap(fn ($category) => $category->items);
    $schema = app(\App\Services\Seo\StructuredData::class)->faq($allItems, fn ($item) => $renderer->html($item->answer)->toHtml());
@endphp

<x-layouts.app :title="__('app.faq.title')" :description="__('app.faq.description')" :schemas="[$schema]">
    <div class="mx-auto max-w-3xl px-4 py-10 sm:px-6">
        <x-breadcrumb :items="[__('app.faq.title') => null]" />
        <h1 class="mt-5 text-3xl font-bold tracking-tight text-brand-900">{{ __('app.faq.title') }}</h1>
        <p class="mt-2 text-slate-600">{{ __('app.faq.intro') }}</p>

        @foreach ($categories as $category)
            <x-faq-list class="mt-10" :items="$category->items" :heading="$category->name" :level="2" :schema="false" />
        @endforeach

        <p class="mt-12 text-sm text-slate-600">{{ __('app.faq.more') }} <a href="{{ route('contact') }}" class="font-medium text-brand-700 hover:underline">{{ __('app.pages.cta_contact') }}</a></p>
    </div>
</x-layouts.app>
