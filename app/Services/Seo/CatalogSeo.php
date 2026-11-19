<?php

namespace App\Services\Seo;

use App\Enums\ProductSort;
use App\Models\Core\Product\Category;
use App\Services\Core\Product\CatalogFilters;

/**
 * Canonical URL and robots directive of a category listing.
 *
 * - clean URL, or a single filter group (subcategory, brands, price, availability): indexable, canonical to itself;
 * - two or more filter groups, a printer filter, or a non-default sort order: `noindex,follow`, canonical to the clean category URL;
 * - pagination keeps `?page=N` in the canonical (unfiltered listings only).
 */
class CatalogSeo
{
    /**
     * @return array{canonical: string, robots: string}
     */
    public function forCategory(Category $category, CatalogFilters $filters, int $page = 1): array
    {
        $base = route('categories.show', $category->slug);
        $groups = $this->activeFilterGroups($filters);
        $sorted = $filters->sort !== ProductSort::Featured;

        if ($sorted || $groups > 1 || $filters->printerNames !== []) {
            return ['canonical' => $base, 'robots' => 'noindex,follow'];
        }

        $query = [];

        if ($filters->subcategorySlug !== null) {
            $query['sous_categorie'] = $filters->subcategorySlug;
        } elseif ($filters->brandSlugs !== []) {
            $query['marques'] = $filters->brandSlugs;
        } elseif ($filters->minPriceDh !== null || $filters->maxPriceDh !== null) {
            $query = array_filter(['prix_min' => $filters->minPriceDh, 'prix_max' => $filters->maxPriceDh], fn ($value) => $value !== null);
        } elseif ($filters->inStockOnly) {
            $query['en_stock'] = 1;
        }

        if ($page > 1) {
            $query['page'] = $page;
        }

        return [
            'canonical' => $query === [] ? $base : $base.'?'.http_build_query($query),
            'robots' => 'index,follow',
        ];
    }

    private function activeFilterGroups(CatalogFilters $filters): int
    {
        return (int) ($filters->subcategorySlug !== null)
            + (int) ($filters->brandSlugs !== [])
            + (int) ($filters->minPriceDh !== null || $filters->maxPriceDh !== null)
            + (int) $filters->inStockOnly;
    }
}
