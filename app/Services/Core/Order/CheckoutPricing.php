<?php

namespace App\Services\Core\Order;

use App\Enums\CarrierMode;
use App\Exceptions\ShippingUnavailable;
use App\Services\Core\Cart\CartSummary;
use App\Services\Core\Shipping\ShippingCalculator;
use App\Settings\GeneralSettings;

class CheckoutPricing
{
    public function __construct(
        private readonly ShippingCalculator $shipping,
        private readonly GeneralSettings $settings,
    ) {}

    /**
     * @throws ShippingUnavailable
     */
    public function quote(CartSummary $cart, string $city, CarrierMode $carrierMode): CheckoutQuote
    {
        $weight = $cart->weightGrams();
        $shipping = $this->shipping->quote($city, $weight, $carrierMode);
        $shippingVat = $shipping->priceHt->percentage($this->settings->vat_rate);
        $vatTotal = $cart->itemsVat()->plus($shippingVat);

        return new CheckoutQuote(
            itemsHt: $cart->itemsHt(),
            shippingHt: $shipping->priceHt,
            vatTotal: $vatTotal,
            totalTtc: $cart->itemsHt()->plus($shipping->priceHt)->plus($vatTotal),
            weightGrams: $weight,
            carrierMode: $carrierMode,
            zone: $shipping->zone,
        );
    }
}
