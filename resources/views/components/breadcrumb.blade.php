@props(['items'])

<script type="application/ld+json">{!! \App\Support\Seo::json(app(\App\Services\Seo\StructuredData::class)->breadcrumbs($items)) !!}</script>

<nav aria-label="{{ __('catalog.breadcrumb') }}" class="text-sm text-slate-500">
    <ol class="flex flex-wrap items-center gap-x-2 gap-y-1">
        <li><a href="{{ route('home') }}" class="hover:text-brand-700">{{ __('catalog.home') }}</a></li>
        @foreach ($items as $label => $url)
            <li aria-hidden="true">/</li>
            <li @if ($loop->last) aria-current="page" class="font-medium text-slate-700" @endif>
                @if ($url && ! $loop->last)
                    <a href="{{ $url }}" class="hover:text-brand-700">{{ $label }}</a>
                @else
                    {{ $label }}
                @endif
            </li>
        @endforeach
    </ol>
</nav>
