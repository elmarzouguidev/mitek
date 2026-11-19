<?php

namespace App\Services\Core\Product;

use App\Models\Core\Product\Category;
use App\Models\Core\Product\Product;
use Illuminate\Support\Facades\Cache;

/**
 * The storefront navigation tree: families, their subcategories with product counts, and one featured
 * product per family. Everything lives in ONE cache entry (three queries when cold, none when warm) that
 * is flushed whenever a product or a category is saved or deleted.
 */
class CatalogMenu
{
    public const FEATURED_PER_FAMILY = 1;

    public function __construct(private readonly PriceCalculator $prices) {}

    /**
     * @return array<int, array{name: string, short_name: string, slug: string, count: int, children: array<int, array{name: string, slug: string, count: int}>, featured: array<int, array{name: string, url: string, price: string, image: string|null}>}>
     */
    public function tree(): array
    {
        return Cache::remember(Category::MENU_CACHE_KEY, now()->addHours(6), fn (): array => $this->build());
    }

    public static function flush(): void
    {
        Cache::forget(Category::MENU_CACHE_KEY);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function build(): array
    {
        $roots = Category::query()
            ->roots()
            ->visible()
            ->ordered()
            ->with(['children' => fn ($children) => $children->where('is_active', true)])
            ->get();

        $counts = Product::query()->visible()->selectRaw('products.category_id, count(*) as total')->groupBy('products.category_id')->pluck('total', 'category_id');

        $familyOf = [];
        foreach ($roots as $root) {
            $familyOf[$root->id] = $root->id;
            foreach ($root->children as $child) {
                $familyOf[$child->id] = $root->id;
            }
        }

        $featured = Product::query()
            ->visible()
            ->where('is_featured', true)
            ->with('media')
            ->orderByDesc('id')
            ->limit(40)
            ->get()
            ->groupBy(fn (Product $product): ?int => $familyOf[$product->category_id] ?? null);

        return $roots->map(fn (Category $root): array => [
            'name' => $root->name,
            'short_name' => $root->menuName(),
            'slug' => $root->slug,
            'count' => (int) ($counts[$root->id] ?? 0) + $root->children->sum(fn (Category $child): int => (int) ($counts[$child->id] ?? 0)),
            'children' => $root->children->map(fn (Category $child): array => [
                'name' => $child->name,
                'slug' => $child->slug,
                'count' => (int) ($counts[$child->id] ?? 0),
            ])->all(),
            'featured' => ($featured[$root->id] ?? collect())->take(self::FEATURED_PER_FAMILY)->map(fn (Product $product): array => [
                'name' => $product->name,
                'url' => route('products.show', $product->slug),
                'price' => $this->prices->withVat($product->price_ht, $this->prices->vatRate($product))->format(),
                'image' => $product->getFirstMediaUrl(Product::GALLERY, 'thumb') ?: null,
            ])->values()->all(),
        ])->all();
    }
}
