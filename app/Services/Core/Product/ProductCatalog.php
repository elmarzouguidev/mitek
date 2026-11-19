<?php

namespace App\Services\Core\Product;

use App\Models\Core\Product\Brand;
use App\Models\Core\Product\Category;
use App\Models\Core\Product\Product;
use App\Settings\GeneralSettings;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

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
        $query = $this->filtered($category, $filters)->with(['media', 'brand', 'priceTiers', 'specifications']);

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
     * Number of results behind each facet value. A facet ignores its own selection (picking a brand does not
     * zero out the other brands) but respects every other active filter, so the numbers always match what
     * the visitor gets by ticking the box.
     *
     * @return array{subcategories: array<int, int>, brands: array<int, int>, printers: array<string, int>, in_stock: int}
     */
    public function facetCounts(Category $category, CatalogFilters $filters): array
    {
        $bySubcategory = $this->filtered($category, $filters, except: ['subcategory'])
            ->reorder()
            ->select('products.category_id', DB::raw('COUNT(*) as total'))
            ->groupBy('products.category_id')
            ->pluck('total', 'products.category_id');

        $byBrand = $this->filtered($category, $filters, except: ['brand'])
            ->reorder()
            ->whereNotNull('products.brand_id')
            ->select('products.brand_id', DB::raw('COUNT(*) as total'))
            ->groupBy('products.brand_id')
            ->pluck('total', 'products.brand_id');

        $byPrinter = $this->filtered($category, $filters, except: ['printer'])
            ->reorder()
            ->join('compatible_model_product', 'compatible_model_product.product_id', '=', 'products.id')
            ->join('compatible_models', 'compatible_models.id', '=', 'compatible_model_product.compatible_model_id')
            ->select('compatible_models.name', DB::raw('COUNT(DISTINCT products.id) as total'))
            ->groupBy('compatible_models.name')
            ->orderBy('compatible_models.name')
            ->pluck('total', 'compatible_models.name');

        $inStock = $this->filtered($category, $filters, except: ['stock'])->inStock()->count();

        return [
            'subcategories' => $bySubcategory->map(fn (mixed $total): int => (int) $total)->all(),
            'brands' => $byBrand->map(fn (mixed $total): int => (int) $total)->all(),
            'printers' => $byPrinter->map(fn (mixed $total): int => (int) $total)->all(),
            'in_stock' => $inStock,
        ];
    }

    /**
     * Printer models any product of the category tree is compatible with (the facet only exists for
     * consumables), with their product count, regardless of the other filters.
     *
     * @return array<string, int>
     */
    public function printersFor(Category $category): array
    {
        return Product::query()
            ->visible()
            ->whereIn('products.category_id', $this->categoryIds($category))
            ->join('compatible_model_product', 'compatible_model_product.product_id', '=', 'products.id')
            ->join('compatible_models', 'compatible_models.id', '=', 'compatible_model_product.compatible_model_id')
            ->select('compatible_models.name', DB::raw('COUNT(DISTINCT products.id) as total'))
            ->groupBy('compatible_models.name')
            ->orderBy('compatible_models.name')
            ->pluck('total', 'compatible_models.name')
            ->map(fn (mixed $total): int => (int) $total)
            ->all();
    }

    /**
     * @param  array<int, string>  $except  filter groups to leave out: subcategory, brand, printer, stock
     * @return Builder<Product>
     */
    private function filtered(Category $category, CatalogFilters $filters, array $except = []): Builder
    {
        $query = Product::query()
            ->visible()
            ->whereIn('products.category_id', $this->categoryIds($category, in_array('subcategory', $except, true) ? null : $filters->subcategorySlug));

        if ($filters->brandSlugs !== [] && ! in_array('brand', $except, true)) {
            $query->whereHas('brand', fn (Builder $brand) => $brand->whereIn('slug', $filters->brandSlugs));
        }

        if ($filters->printerNames !== [] && ! in_array('printer', $except, true)) {
            $query->whereHas('compatibleModels', fn (Builder $model) => $model->whereIn('compatible_models.name', $filters->printerNames));
        }

        if ($filters->inStockOnly && ! in_array('stock', $except, true)) {
            $query->inStock();
        }

        $this->applyPriceRange($query, $filters);

        return $query;
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
