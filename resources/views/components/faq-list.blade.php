@props(['items', 'heading' => null, 'level' => 2, 'schema' => true])

@php
    $renderer = app(\App\Services\Content\ContentRenderer::class);
    $faqSchema = $schema ? app(\App\Services\Seo\StructuredData::class)->faq($items, fn ($item) => $renderer->html($item->answer)->toHtml()) : null;
@endphp

@if ($items->isNotEmpty())
    @if ($faqSchema)
        <script type="application/ld+json">{!! \App\Support\Seo::json($faqSchema) !!}</script>
    @endif

    <section {{ $attributes }}>
        @if ($heading)
            <h{{ $level }} class="text-2xl font-semibold tracking-tight text-brand-900">{{ $heading }}</h{{ $level }}>
        @endif
        <div class="mt-4 divide-y divide-slate-200 rounded-xl ring-1 ring-slate-200">
            @foreach ($items as $item)
                <details class="group px-5 py-4">
                    <summary class="flex cursor-pointer list-none items-center justify-between gap-4 text-left font-medium text-slate-900 marker:hidden [&::-webkit-details-marker]:hidden">
                        {{ $item->question }}
                        <svg class="size-5 shrink-0 text-slate-400 transition group-open:rotate-180" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M5.2 7.2a.75.75 0 0 1 1.06 0L10 10.94l3.74-3.74a.75.75 0 1 1 1.06 1.06l-4.27 4.27a.75.75 0 0 1-1.06 0L5.2 8.26a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd"/></svg>
                    </summary>
                    <div class="prose-mitek mt-3 text-slate-600">{{ $renderer->html($item->answer) }}</div>
                </details>
            @endforeach
        </div>
    </section>
@endif
