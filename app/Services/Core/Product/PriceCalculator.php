<?php

namespace App\Services\Core\Product;

use App\Models\Core\Product\Product;
use App\Models\Core\Product\ProductPriceTier;
use App\Settings\GeneralSettings;
use App\Support\Money;
use Carbon\CarbonImmutable;

/**
 * Single source of truth for product prices: used by the catalog, the product page and (Phase 3) the cart.
 *
 * Prices are stored HT. The unit price for a quantity is the price of the highest tier whose
 * minimum quantity is reached, or the base price. VAT is applied per unit for display (rounded half
 * up to the centime) and on the line total for amounts that will be invoiced.
 *
 * Callers must load the product's `priceTiers` relation beforehand (lazy loading is disabled).
 */
class PriceCalculator
{
    public function __construct(private readonly GeneralSettings $settings) {}

    /**
     * VAT percentage of a product: its own rate, or the default from the settings.
     */
    public function vatRate(Product $product): int
    {
        return $product->vat_rate ?? $this->settings->vat_rate;
    }

    public function unitPriceHt(Product $product, int $quantity = 1): Money
    {
        return $this->applicableTier($product, $quantity)?->price_ht ?? $product->price_ht;
    }

    public function withVat(Money $amountHt, int $vatRate): Money
    {
        return $amountHt->plus($amountHt->percentage($vatRate));
    }

    public function unitPriceTtc(Product $product, int $quantity = 1): Money
    {
        return $this->withVat($this->unitPriceHt($product, $quantity), $this->vatRate($product));
    }

    public function quote(Product $product, int $quantity = 1): PriceQuote
    {
        $quantity = max(1, $quantity);
        $vatRate = $this->vatRate($product);
        $unitHt = $this->unitPriceHt($product, $quantity);
        $lineHt = $unitHt->times($quantity);
        $lineVat = $lineHt->percentage($vatRate);

        return new PriceQuote(
            quantity: $quantity,
            vatRate: $vatRate,
            unitHt: $unitHt,
            unitTtc: $this->withVat($unitHt, $vatRate),
            lineHt: $lineHt,
            lineVat: $lineVat,
            lineTtc: $lineHt->plus($lineVat),
            tierApplied: $this->applicableTier($product, $quantity) !== null,
        );
    }

    /**
     * The running promotion of a product, or null. It compares the previous price with the base unit price
     * (quantity tiers are separate B2B prices and never carry a struck-through price).
     */
    public function promo(Product $product): ?Promo
    {
        $compareAt = $product->compare_at_price_ht;

        if ($compareAt === null || ! $compareAt->isGreaterThan($product->price_ht)) {
            return null;
        }

        if ($product->promo_ends_at !== null && $product->promo_ends_at->isPast()) {
            return null;
        }

        $vatRate = $this->vatRate($product);
        $compareAtTtc = $this->withVat($compareAt, $vatRate);

        return new Promo(
            compareAtHt: $compareAt,
            compareAtTtc: $compareAtTtc,
            savingTtc: $compareAtTtc->minus($this->unitPriceTtc($product)),
            percent: max(1, (int) round(($compareAt->cents - $product->price_ht->cents) / $compareAt->cents * 100)),
            endsAt: $product->promo_ends_at === null ? null : CarbonImmutable::instance($product->promo_ends_at),
        );
    }

    /**
     * Quantity ranges with their unit prices, for the tier table of the product page.
     * Empty when the product has no tiers.
     *
     * @return array<int, array{from: int, to: int|null, unit_ht: Money, unit_ttc: Money}>
     */
    public function tierTable(Product $product): array
    {
        if ($product->priceTiers->isEmpty()) {
            return [];
        }

        $vatRate = $this->vatRate($product);
        $steps = collect([['from' => 1, 'unit_ht' => $product->price_ht]])
            ->merge($product->priceTiers->sortBy('min_quantity')->map(fn (ProductPriceTier $tier) => [
                'from' => $tier->min_quantity,
                'unit_ht' => $tier->price_ht,
            ]))
            ->values();

        return $steps->map(fn (array $step, int $index): array => [
            'from' => $step['from'],
            'to' => isset($steps[$index + 1]) ? $steps[$index + 1]['from'] - 1 : null,
            'unit_ht' => $step['unit_ht'],
            'unit_ttc' => $this->withVat($step['unit_ht'], $vatRate),
        ])->all();
    }

    private function applicableTier(Product $product, int $quantity): ?ProductPriceTier
    {
        return $product->priceTiers
            ->filter(fn (ProductPriceTier $tier) => $tier->min_quantity <= $quantity)
            ->sortByDesc('min_quantity')
            ->first();
    }
}
