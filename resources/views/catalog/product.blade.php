@php
    $calculator = app(\App\Services\Core\Product\PriceCalculator::class);
    $images = $product->getMedia(\App\Models\Core\Product\Product::GALLERY)->map(fn ($media) => [
        'thumb' => $media->getUrl('thumb'),
        'large' => $media->getUrl('large'),
        'alt' => $product->imageAlt(),
    ])->values();
    $quote = $calculator->quote($product, 1);
    $whatsapp = app(\App\Support\WhatsApp::class);
    $whatsappBase = $whatsapp->link($whatsapp->productMessage($product));
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

                <form method="POST" action="{{ route('cart.store') }}" @submit.prevent="$dispatch('cart:add', { form: $event.target })" class="mt-6 flex flex-wrap items-end gap-3">
                    @csrf
                    <input type="hidden" name="product" value="{{ $product->slug }}">
                    <div>
                        <label for="quantity" class="block text-sm font-medium text-slate-700">{{ __('catalog.product.quantity') }}</label>
                        <input id="quantity" name="quantite" type="number" min="1" max="{{ max(1, $product->stock_quantity) }}" value="1" x-model.number="quantity" @input="update()" @disabled(! $product->isInStock()) class="mt-1 w-24 rounded-lg border-0 px-3 py-2 text-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-brand-600">
                    </div>
                    <button type="submit" @disabled(! $product->isInStock()) class="rounded-lg bg-brand-700 px-6 py-2.5 text-sm font-semibold text-white hover:bg-brand-800 disabled:cursor-not-allowed disabled:opacity-50">{{ __('catalog.product.add_to_cart') }}</button>
                </form>

                @if ($whatsappBase)
                    <a
                        href="{{ $whatsappBase }}"
                        :href="@js($whatsappBase) + encodeURIComponent('\n' + @js(__('app.whatsapp.quantity_line')).replace(':quantity', Math.max(1, parseInt(quantity, 10) || 1)))"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="mt-3 inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-6 py-2.5 text-sm font-semibold text-white transition hover:bg-emerald-700 active:scale-[0.98]"
                    >
                        <svg class="size-5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12.04 2a9.9 9.9 0 0 0-8.4 15.1L2 22l5.05-1.6A9.9 9.9 0 1 0 12.04 2Zm4.5 12c-.25-.12-1.45-.72-1.68-.8-.22-.08-.39-.12-.55.12s-.63.8-.77.96-.28.18-.52.06a6.7 6.7 0 0 1-3.3-2.9c-.25-.43.25-.4.72-1.33.08-.16.04-.3-.02-.42s-.55-1.32-.75-1.8c-.2-.47-.4-.4-.55-.41h-.47c-.16 0-.42.06-.64.3s-.84.82-.84 2 .86 2.32.98 2.48c.12.16 1.7 2.6 4.1 3.65 1.5.65 2.1.7 2.86.6.46-.07 1.45-.6 1.65-1.17s.2-1.07.14-1.17-.22-.16-.47-.28Z"/></svg>
                        {{ __('app.whatsapp.order_product') }}<span class="sr-only"> {{ __('app.whatsapp.new_tab') }}</span>
                    </a>
                @endif

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
