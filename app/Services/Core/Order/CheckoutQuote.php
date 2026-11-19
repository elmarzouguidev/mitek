<?php

namespace App\Services\Core\Order;

use App\Enums\CarrierMode;
use App\Models\Core\Shipping\ShippingZone;
use App\Support\Money;
use JsonSerializable;

/**
 * Amounts of an order before it is placed: items, shipping, VAT and the TTC total.
 */
final readonly class CheckoutQuote implements JsonSerializable
{
    public function __construct(
        public Money $itemsHt,
        public Money $shippingHt,
        public Money $vatTotal,
        public Money $totalTtc,
        public int $weightGrams,
        public CarrierMode $carrierMode,
        public ?ShippingZone $zone,
    ) {}

    public function subtotalHt(): Money
    {
        return $this->itemsHt;
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return [
            'items_ht' => $this->itemsHt,
            'shipping_ht' => $this->shippingHt,
            'vat_total' => $this->vatTotal,
            'total_ttc' => $this->totalTtc,
            'weight_grams' => $this->weightGrams,
            'zone' => $this->zone?->name,
        ];
    }
}
