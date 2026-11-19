<?php

namespace App\Services\Core\Product;

use App\Enums\ProductSort;

/**
 * Listing filters read from the query string (they live in the URL so they can be shared and crawled).
 */
final readonly class CatalogFilters
{
    /**
     * @param  array<int, string>  $brandSlugs
     * @param  array<int, string>  $printerNames  compatible printer models (exact names)
     */
    public function __construct(
        public ?string $subcategorySlug = null,
        public array $brandSlugs = [],
        public ?int $minPriceDh = null,
        public ?int $maxPriceDh = null,
        public bool $inStockOnly = false,
        public ProductSort $sort = ProductSort::Featured,
        public array $printerNames = [],
    ) {}
}
