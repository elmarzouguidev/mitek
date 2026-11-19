<?php

namespace App\Services\Core\Product;

use App\Models\Core\Product\Brand;
use App\Models\Core\Product\Category;
use App\Models\Core\Product\Product;
use App\Settings\GeneralSettings;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/**
 * Builds the visible product listing of a category and applies the storefront filters.
 */
class ProductCatalog
{
    public const PER_PAGE = 12;

    public function __construct(private readonly GeneralSettings $settings) {}

    /**
     * Visible products of the category and of its active subcategories, with the card relations loaded.
     *
     * @return Builder<Product>
     */
    public function listing(Category $category, CatalogFilters $filters): Builder
    {
        $query = Product::query()
            ->visible()
            ->with(['media', 'brand', 'priceTiers'])
            ->whereIn('products.category_id', $this->categoryIds($category, $filters->subcategorySlug));

        if ($filters->brandSlugs !== []) {
            $query->whereHas('brand', fn (Builder $brand) => $brand->whereIn('slug', $filters->brandSlugs));
        }

        if ($filters->inStockOnly) {
            $query->inStock();
        }

        $this->applyPriceRange($query, $filters);

        return $filters->sort->apply($query);
    }

    /**
     * Active brands that have at least one visible product in the category tree.
     *
     * @return Collection<int, Brand>
     */
    public function brandsFor(Category $category): Collection
    {
        return Brand::query()
            ->active()
            ->whereIn('id', Product::query()
                ->visible()
                ->whereIn('category_id', $this->categoryIds($category))
                ->whereNotNull('brand_id')
                ->select('brand_id'))
            ->orderBy('name')
            ->get();
    }

    /**
     * @return array<int, int>
     */
    private function categoryIds(Category $category, ?string $subcategorySlug = null): array
    {
        $children = $category->children->where('is_active', true);

        if ($subcategorySlug !== null) {
            $selected = $children->firstWhere('slug', $subcategorySlug);

            if ($selected !== null) {
                return [$selected->id];
            }
        }

        return [$category->id, ...$children->pluck('id')->all()];
    }

    /**
     * The visitor types whole dirhams TTC. price_ht (centimes) × (100 + VAT %) is the TTC amount in
     * centimes × 100, so the bounds are multiplied by 100 × 100.
     *
     * @param  Builder<Product>  $query
     */
    private function applyPriceRange(Builder $query, CatalogFilters $filters): void
    {
        $ttcCents = 'products.price_ht * (100 + COALESCE(products.vat_rate, ?))';

        if ($filters->minPriceDh !== null) {
            $query->whereRaw("{$ttcCents} >= ?", [$this->settings->vat_rate, $filters->minPriceDh * 10000]);
        }

        if ($filters->maxPriceDh !== null) {
            $query->whereRaw("{$ttcCents} <= ?", [$this->settings->vat_rate, $filters->maxPriceDh * 10000]);
        }
    }
}
