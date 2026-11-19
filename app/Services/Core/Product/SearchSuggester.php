<?php

namespace App\Services\Core\Product;

use App\Models\Core\Product\Brand;
use App\Models\Core\Product\Category;
use App\Models\Core\Product\CompatibleModel;
use App\Models\Core\Product\Product;
use App\Models\Core\Service\Service;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * Grouped autocomplete suggestions (products, categories, brands, printer models, services), at most
 * {@see self::PER_GROUP} per group. Results are cached for a short time per normalised term.
 */
class SearchSuggester
{
    public const PER_GROUP = 5;

    public const MIN_LENGTH = 2;

    public const CACHE_SECONDS = 60;

    public function __construct(
        private readonly ProductSearch $search,
        private readonly PriceCalculator $prices,
    ) {}

    /**
     * @return array{term: string, groups: array<int, array{key: string, label: string, items: array<int, array<string, mixed>>}>, all_url: string}
     */
    public function suggest(string $term): array
    {
        $term = Str::squish($term);

        $groups = mb_strlen($term) < self::MIN_LENGTH
            ? []
            : Cache::remember('search.suggestions.'.md5(mb_strtolower($term)), self::CACHE_SECONDS, fn (): array => $this->build($term));

        return [
            'term' => $term,
            'groups' => $groups,
            'all_url' => route('search', ['q' => $term]),
        ];
    }

    /**
     * @return array<int, array{key: string, label: string, items: array<int, array<string, mixed>>}>
     */
    private function build(string $term): array
    {
        $like = '%'.str_replace(['|', '%', '_'], ['||', '|%', '|_'], $term).'%';

        $groups = [
            ['key' => 'products', 'label' => __('catalog.search.groups.products'), 'items' => $this->products($term)],
            ['key' => 'categories', 'label' => __('catalog.search.groups.categories'), 'items' => $this->categories($like)],
            ['key' => 'brands', 'label' => __('catalog.search.groups.brands'), 'items' => $this->brands($like)],
            ['key' => 'printers', 'label' => __('catalog.search.groups.printers'), 'items' => $this->printers($like)],
            ['key' => 'services', 'label' => __('catalog.search.groups.services'), 'items' => $this->services($like)],
        ];

        return array_values(array_filter($groups, fn (array $group): bool => $group['items'] !== []));
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function products(string $term): array
    {
        return $this->search->search($term)->limit(self::PER_GROUP)->get()->map(fn (Product $product): array => [
            'label' => $product->name,
            'sub' => collect([$product->brand?->name, $product->reference])->filter()->implode(' · '),
            'price' => $this->prices->unitPriceTtc($product)->format(),
            'image' => $product->getFirstMediaUrl(Product::GALLERY, 'thumb') ?: null,
            'url' => route('products.show', $product->slug),
        ])->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function categories(string $like): array
    {
        return Category::query()->visible()->with('parent')
            ->whereRaw("name LIKE ? ESCAPE '|'", [$like])
            ->ordered()->limit(self::PER_GROUP)->get()
            ->map(fn (Category $category): array => [
                'label' => $category->name,
                'sub' => $category->parent?->name,
                'url' => route('categories.show', $category->slug),
            ])->all();
    }

    /**
     * Brands only lead to the search results for now: they get their own page later.
     *
     * @return array<int, array<string, mixed>>
     */
    private function brands(string $like): array
    {
        return Brand::query()->active()
            ->whereRaw("name LIKE ? ESCAPE '|'", [$like])
            ->whereHas('products', fn (Builder $products) => $products->visible())
            ->orderBy('name')->limit(self::PER_GROUP)->get(['id', 'name'])
            ->map(fn (Brand $brand): array => [
                'label' => $brand->name,
                'sub' => null,
                'url' => route('search', ['q' => $brand->name]),
            ])->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function printers(string $like): array
    {
        return CompatibleModel::query()
            ->whereRaw("name LIKE ? ESCAPE '|'", [$like])
            ->whereHas('products', fn (Builder $products) => $products->visible())
            ->orderBy('name')->limit(self::PER_GROUP)->get(['id', 'name'])
            ->map(fn (CompatibleModel $model): array => [
                'label' => $model->name,
                'sub' => __('catalog.search.printer_hint'),
                'url' => route('search', ['q' => $model->name]),
            ])->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function services(string $like): array
    {
        return Service::query()->active()->ordered()
            ->where(fn (Builder $match) => $match->whereRaw("title LIKE ? ESCAPE '|'", [$like])->orWhereRaw("summary LIKE ? ESCAPE '|'", [$like]))
            ->limit(self::PER_GROUP)->get(['id', 'title', 'slug'])
            ->map(fn (Service $service): array => [
                'label' => $service->title,
                'sub' => null,
                'url' => route('services.show', $service->slug),
            ])->all();
    }
}
