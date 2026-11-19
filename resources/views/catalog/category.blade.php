@php
    $crumbs = [];
    if ($category->parent) {
        $crumbs[$category->parent->name] = route('categories.show', $category->parent->slug);
    }
    $crumbs[$category->name] = null;
    $hasActiveFilters = $filters->subcategorySlug || $filters->brandSlugs || $filters->minPriceDh !== null || $filters->maxPriceDh !== null || $filters->inStockOnly;
@endphp

<x-layouts.app :title="$category->meta_title ?: $category->name" :description="$category->meta_description ?: \App\Support\Seo::description($category->description) ?: __('catalog.category_description', ['name' => $category->name])" :canonical="$seo['canonical']" :robots="$seo['robots']">
    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8" x-data="{ drawer: false }">
        <x-breadcrumb :items="$crumbs" />

        <header class="mt-4">
            <h1 class="text-3xl font-bold tracking-tight text-brand-900">{{ $category->name }}</h1>
            @if ($category->description)
                <p class="mt-2 max-w-3xl text-slate-600">{{ $category->description }}</p>
            @endif
        </header>

        <div class="mt-6 flex items-center justify-between gap-4 lg:hidden">
            <button type="button" x-cloak x-show="true" class="rounded-lg px-4 py-2 text-sm font-medium ring-1 ring-slate-300 hover:bg-slate-50" @click="drawer = true">{{ __('catalog.filters') }}</button>
        </div>

        <div class="mt-6 grid gap-8 lg:grid-cols-[16rem_1fr]">
            <aside :class="drawer ? 'max-lg:fixed max-lg:inset-0 max-lg:z-50 max-lg:overflow-y-auto max-lg:bg-white max-lg:p-6' : 'max-lg:hidden'" aria-label="{{ __('catalog.filters') }}">
                <div class="mb-4 flex items-center justify-between lg:hidden" x-cloak x-show="drawer">
                    <p class="text-lg font-semibold">{{ __('catalog.filters') }}</p>
                    <button type="button" class="rounded-lg p-2 hover:bg-slate-100" aria-label="Fermer" @click="drawer = false">✕</button>
                </div>

                <form method="GET" action="{{ route('categories.show', $category->slug) }}" class="space-y-6 text-sm" @change="$el.requestSubmit()">
                    @if ($subcategories->isNotEmpty())
                        <fieldset>
                            <legend class="font-semibold text-slate-900">{{ __('catalog.subcategory') }}</legend>
                            <div class="mt-2 space-y-1.5">
                                <label class="flex items-center gap-2"><input type="radio" name="sous_categorie" value="" @checked(! $filters->subcategorySlug) class="text-brand-700 focus:ring-brand-600"> {{ __('catalog.all_subcategories') }}</label>
                                @foreach ($subcategories as $child)
                                    <label class="flex items-center gap-2"><input type="radio" name="sous_categorie" value="{{ $child->slug }}" @checked($filters->subcategorySlug === $child->slug) class="text-brand-700 focus:ring-brand-600"> {{ $child->name }}</label>
                                @endforeach
                            </div>
                        </fieldset>
                    @endif

                    @if ($brands->isNotEmpty())
                        <fieldset>
                            <legend class="font-semibold text-slate-900">{{ __('catalog.brand') }}</legend>
                            <div class="mt-2 space-y-1.5">
                                @foreach ($brands as $brand)
                                    <label class="flex items-center gap-2"><input type="checkbox" name="marques[]" value="{{ $brand->slug }}" @checked(in_array($brand->slug, $filters->brandSlugs, true)) class="rounded text-brand-700 focus:ring-brand-600"> {{ $brand->name }}</label>
                                @endforeach
                            </div>
                        </fieldset>
                    @endif

                    <fieldset>
                        <legend class="font-semibold text-slate-900">{{ __('catalog.price_range') }}</legend>
                        <div class="mt-2 flex items-center gap-2">
                            <input type="number" name="prix_min" min="0" inputmode="numeric" placeholder="{{ __('catalog.min') }}" value="{{ $filters->minPriceDh }}" class="w-full rounded-lg border-0 px-3 py-1.5 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-brand-600" aria-label="{{ __('catalog.min') }}">
                            <span aria-hidden="true">–</span>
                            <input type="number" name="prix_max" min="0" inputmode="numeric" placeholder="{{ __('catalog.max') }}" value="{{ $filters->maxPriceDh }}" class="w-full rounded-lg border-0 px-3 py-1.5 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-brand-600" aria-label="{{ __('catalog.max') }}">
                        </div>
                    </fieldset>

                    <label class="flex items-center gap-2 font-medium text-slate-900"><input type="checkbox" name="en_stock" value="1" @checked($filters->inStockOnly) class="rounded text-brand-700 focus:ring-brand-600"> {{ __('catalog.in_stock_only') }}</label>

                    <div>
                        <label for="tri" class="font-semibold text-slate-900">{{ __('catalog.sort_by') }}</label>
                        <select id="tri" name="tri" class="mt-2 block w-full rounded-lg border-0 py-1.5 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-brand-600">
                            @foreach ($sorts as $sort)
                                <option value="{{ $sort->value }}" @selected($filters->sort === $sort)>{{ $sort->label() }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="flex items-center gap-3">
                        <button type="submit" class="rounded-lg bg-brand-700 px-4 py-2 font-semibold text-white hover:bg-brand-800" @click="drawer = false">{{ __('catalog.apply') }}</button>
                        @if ($hasActiveFilters)
                            <a href="{{ route('categories.show', $category->slug) }}" class="text-brand-700 hover:underline">{{ __('catalog.reset_filters') }}</a>
                        @endif
                    </div>
                </form>
            </aside>

            <section aria-live="polite">
                <p class="mb-4 text-sm text-slate-500">{{ trans_choice('catalog.results', $products->total()) }}</p>

                @if ($products->isEmpty())
                    <p class="rounded-xl bg-slate-50 p-8 text-center text-slate-600">{{ __('catalog.no_results') }}</p>
                @else
                    <div class="grid gap-5 sm:grid-cols-2 xl:grid-cols-3">
                        @foreach ($products as $product)
                            <x-product-card :product="$product" :level="2" />
                        @endforeach
                    </div>

                    <div class="mt-8">{{ $products->links() }}</div>
                @endif
            </section>
        </div>
    </div>
</x-layouts.app>
