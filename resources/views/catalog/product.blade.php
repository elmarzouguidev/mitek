@php
    $calculator = app(\App\Services\Core\Product\PriceCalculator::class);
    $images = $product->getMedia(\App\Models\Core\Product\Product::GALLERY)->map(fn ($media) => [
        'thumb' => $media->getUrl('thumb'),
        'large' => $media->getUrl('large'),
        'alt' => $product->imageAlt(),
    ])->values();
    $quote = $calculator->quote($product, 1);
    $tierTable = $calculator->tierTable($product);

    $crumbs = [];
    if ($product->category->parent) {
        $crumbs[$product->category->parent->name] = route('categories.show', $product->category->parent->slug);
    }
    $crumbs[$product->category->name] = route('categories.show', $product->category->slug);
    $crumbs[$product->name] = null;
@endphp

<x-layouts.app :title="$product->meta_title ?: $product->name" :description="$product->meta_description ?: \App\Support\Seo::description($product->excerpt ?: $product->description) ?: $product->name" og-type="product" :image="$images->isNotEmpty() ? $images[0]['large'] : null" :schemas="[app(\App\Services\Seo\StructuredData::class)->product($product)]">
    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <x-breadcrumb :items="$crumbs" />

        <div class="mt-6 grid gap-10 lg:grid-cols-2">
            <div x-data="gallery(@js($images))" @keydown.left="images.length > 1 && previous()" @keydown.right="images.length > 1 && next()">
                <div class="aspect-square overflow-hidden rounded-xl bg-slate-50 ring-1 ring-slate-200">
                    @if ($images->isNotEmpty())
                        <img src="{{ $images[0]['large'] }}" :src="current?.large" alt="{{ $product->imageAlt() }}" :alt="current?.alt" class="size-full object-contain p-6">
                    @else
                        <x-product-image :product="$product" conversion="large" class="size-full" />
                    @endif
                </div>

                @if ($images->count() > 1)
                    <ul class="mt-3 flex flex-wrap gap-2">
                        @foreach ($images as $image)
                            <li>
                                <button type="button" @click="select({{ $loop->index }})" :class="index === {{ $loop->index }} ? 'ring-2 ring-brand-600' : 'ring-1 ring-slate-200'" class="block size-16 overflow-hidden rounded-lg bg-white ring-1 ring-slate-200" aria-label="Image {{ $loop->iteration }}">
                                    <img src="{{ $image['thumb'] }}" alt="" class="size-full object-cover" loading="lazy">
                                </button>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>

            <div x-data="priceBox('{{ route('products.price', $product->slug) }}', @js($quote))">
                @if ($product->brand)
                    <p class="text-sm font-semibold text-brand-600">{{ $product->brand->name }}</p>
                @endif
                <h1 class="mt-1 text-3xl font-bold tracking-tight text-brand-900">{{ $product->name }}</h1>
                <p class="mt-1 text-sm text-slate-500">{{ __('catalog.reference') }} {{ $product->reference }}</p>

                @if ($product->excerpt)
                    <p class="mt-4 text-slate-600">{{ $product->excerpt }}</p>
                @endif

                <div class="mt-6 rounded-xl bg-brand-50 p-5">
                    <p class="text-sm text-slate-600">{{ __('catalog.unit_price') }}</p>
                    <p class="text-3xl font-bold text-brand-900"><span x-text="quote.unit_ttc.formatted">{{ $quote->unitTtc }}</span> <span class="text-base font-medium text-slate-500">{{ __('catalog.ttc') }}</span></p>
                    <p class="text-sm text-slate-600"><span x-text="quote.unit_ht.formatted">{{ $quote->unitHt }}</span> {{ __('catalog.ht') }}</p>
                    <p x-cloak x-show="quote.tier_applied" class="mt-1 text-xs font-medium text-emerald-700">{{ __('catalog.product.tier_applied') }}</p>

                    <p x-cloak x-show="quote.quantity > 1" class="mt-3 border-t border-brand-100 pt-3 text-sm text-slate-700">
                        {{ __('catalog.total_ttc') }} : <strong class="text-brand-900" x-text="quote.line_ttc.formatted"></strong>
                    </p>
                </div>

                <div class="mt-4"><x-stock-badge :product="$product" /></div>

                <form method="POST" action="{{ route('cart.store') }}" class="mt-6 flex flex-wrap items-end gap-3">
                    @csrf
                    <input type="hidden" name="product" value="{{ $product->slug }}">
                    <div>
                        <label for="quantity" class="block text-sm font-medium text-slate-700">{{ __('catalog.product.quantity') }}</label>
                        <input id="quantity" name="quantite" type="number" min="1" max="{{ max(1, $product->stock_quantity) }}" value="1" x-model.number="quantity" @input="update()" @disabled(! $product->isInStock()) class="mt-1 w-24 rounded-lg border-0 px-3 py-2 text-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-brand-600">
                    </div>
                    <button type="submit" @disabled(! $product->isInStock()) class="rounded-lg bg-brand-700 px-6 py-2.5 text-sm font-semibold text-white hover:bg-brand-800 disabled:cursor-not-allowed disabled:opacity-50">{{ __('catalog.product.add_to_cart') }}</button>
                </form>

                @if ($tierTable !== [])
                    <section class="mt-8" aria-labelledby="tiers-title">
                        <h2 id="tiers-title" class="text-lg font-semibold text-brand-900">{{ __('catalog.product.tiers_title') }}</h2>
                        <p class="text-sm text-slate-500">{{ __('catalog.product.tiers_hint') }}</p>
                        <table class="mt-3 w-full text-sm">
                            <thead class="text-left text-slate-500">
                                <tr>
                                    <th scope="col" class="py-2 font-medium">{{ __('catalog.product.quantity_range') }}</th>
                                    <th scope="col" class="py-2 text-right font-medium">{{ __('catalog.ht') }}</th>
                                    <th scope="col" class="py-2 text-right font-medium">{{ __('catalog.ttc') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @foreach ($tierTable as $row)
                                    <tr>
                                        <td class="py-2">{{ $row['to'] === null ? $row['from'].' '.__('catalog.product.and_more') : ($row['from'] === $row['to'] ? $row['from'] : $row['from'].' – '.$row['to']) }}</td>
                                        <td class="py-2 text-right">{{ $row['unit_ht'] }}</td>
                                        <td class="py-2 text-right font-semibold text-brand-900">{{ $row['unit_ttc'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </section>
                @endif
            </div>
        </div>

        <div class="mt-12 grid gap-10 lg:grid-cols-2">
            @if ($product->description)
                <section>
                    <h2 class="text-xl font-semibold text-brand-900">{{ __('catalog.product.description') }}</h2>
                    <div class="mt-3 space-y-3 text-slate-700">
                        @foreach (preg_split('/\R{2,}/', trim($product->description)) as $paragraph)
                            <p>{{ $paragraph }}</p>
                        @endforeach
                    </div>
                </section>
            @endif

            <div class="space-y-10">
                @if ($product->specifications->isNotEmpty())
                    <section>
                        <h2 class="text-xl font-semibold text-brand-900">{{ __('catalog.product.specifications') }}</h2>
                        <dl class="mt-3 divide-y divide-slate-100 rounded-xl ring-1 ring-slate-200">
                            @foreach ($product->specifications as $specification)
                                <div class="grid grid-cols-3 gap-4 px-4 py-2.5 text-sm">
                                    <dt class="font-medium text-slate-600">{{ $specification->name }}</dt>
                                    <dd class="col-span-2 text-slate-900">{{ $specification->value }}</dd>
                                </div>
                            @endforeach
                        </dl>
                    </section>
                @endif

                @if ($product->compatibleModels->isNotEmpty())
                    <section>
                        <h2 class="text-xl font-semibold text-brand-900">{{ __('catalog.product.compatible') }}</h2>
                        <ul class="mt-3 flex flex-wrap gap-2">
                            @foreach ($product->compatibleModels as $model)
                                <li><a href="{{ route('search', ['q' => $model->name]) }}" class="inline-block rounded-full bg-slate-100 px-3 py-1 text-sm text-slate-700 hover:bg-brand-100 hover:text-brand-800">{{ $model->name }}</a></li>
                            @endforeach
                        </ul>
                    </section>
                @endif
            </div>
        </div>

        @if ($related->isNotEmpty())
            <section class="mt-14">
                <h2 class="text-xl font-semibold text-brand-900">{{ __('catalog.product.related') }}</h2>
                <div class="mt-4 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach ($related as $item)
                        <x-product-card :product="$item" />
                    @endforeach
                </div>
            </section>
        @endif
    </div>
</x-layouts.app>
