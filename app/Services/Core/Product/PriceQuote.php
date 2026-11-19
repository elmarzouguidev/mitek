<?php

namespace App\Services\Core\Product;

use App\Support\Money;
use JsonSerializable;

/**
 * Price of a quantity of one product: unit price resolved from the tiers, VAT and totals.
 */
final readonly class PriceQuote implements JsonSerializable
{
    public function __construct(
        public int $quantity,
        public int $vatRate,
        public Money $unitHt,
        public Money $unitTtc,
        public Money $lineHt,
        public Money $lineVat,
        public Money $lineTtc,
        public bool $tierApplied,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return [
            'quantity' => $this->quantity,
            'vat_rate' => $this->vatRate,
            'unit_ht' => $this->unitHt,
            'unit_ttc' => $this->unitTtc,
            'line_ht' => $this->lineHt,
            'line_vat' => $this->lineVat,
            'line_ttc' => $this->lineTtc,
            'tier_applied' => $this->tierApplied,
        ];
    }
}
