<x-layouts.app :title="$term !== '' ? __('catalog.search.results_for', ['term' => $term]) : __('catalog.search.title')">
    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <x-breadcrumb :items="[__('catalog.search.title') => null]" />

        <h1 class="mt-4 text-3xl font-bold tracking-tight text-brand-900">
            {{ $term !== '' ? __('catalog.search.results_for', ['term' => $term]) : __('catalog.search.title') }}
        </h1>

        @if ($products === null)
            <p class="mt-6 rounded-xl bg-slate-50 p-8 text-center text-slate-600">{{ __('catalog.search.too_short') }}</p>
        @else
            <p class="mt-2 text-sm text-slate-500">{{ trans_choice('catalog.results', $products->total()) }}</p>

            @if ($products->isEmpty())
                <p class="mt-6 rounded-xl bg-slate-50 p-8 text-center text-slate-600">{{ __('catalog.no_results') }}</p>
            @else
                <div class="mt-6 grid gap-5 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                    @foreach ($products as $product)
                        <x-product-card :product="$product" />
                    @endforeach
                </div>
                <div class="mt-8">{{ $products->links() }}</div>
            @endif
        @endif
    </div>
</x-layouts.app>
