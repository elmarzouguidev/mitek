<?php

namespace App\Services\Core\Product;

use App\Models\Core\Product\Product;
use App\Settings\CatalogSettings;

/**
 * Badges shown on product cards and the product page: running promotion, best seller (manual flag) and
 * new arrival (added within the number of days set in the catalogue settings). Uses attributes only, so
 * it is safe to call for every card of a listing.
 */
class ProductBadges
{
    public function __construct(
        private readonly PriceCalculator $prices,
        private readonly CatalogSettings $settings,
    ) {}

    /**
     * @return array<int, array{key: string, label: string, tone: string}>
     */
    public function for(Product $product): array
    {
        $badges = [];

        if ($promo = $this->prices->promo($product)) {
            $badges[] = ['key' => 'promo', 'label' => __('catalog.badges.promo', ['percent' => $promo->percent]), 'tone' => 'red'];
        }

        if ($product->is_best_seller) {
            $badges[] = ['key' => 'best_seller', 'label' => __('catalog.badges.best_seller'), 'tone' => 'brand'];
        }

        if ($this->settings->new_badge_days > 0 && $product->created_at?->greaterThanOrEqualTo(now()->subDays($this->settings->new_badge_days))) {
            $badges[] = ['key' => 'new', 'label' => __('catalog.badges.new'), 'tone' => 'green'];
        }

        return $badges;
    }
}
