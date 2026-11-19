<?php

namespace App\Services\Core\Shipping;

use App\Enums\CarrierMode;
use App\Exceptions\ShippingUnavailable;
use App\Models\Core\Shipping\ShippingRate;
use App\Models\Core\Shipping\ShippingZone;
use App\Models\Core\Shipping\ShippingZoneCity;
use App\Settings\ShippingSettings;
use App\Support\Money;

/**
 * Shipping cost from the configurable grid: the city picks a zone (unmapped cities fall back to the
 * default zone) and the total weight picks the bracket. Customers using their own carrier pay the
 * configured extra fee instead.
 */
class ShippingCalculator
{
    public function __construct(private readonly ShippingSettings $settings) {}

    /**
     * @throws ShippingUnavailable when no zone or no weight bracket matches
     */
    public function quote(string $city, int $weightGrams, CarrierMode $mode): ShippingQuote
    {
        if ($mode === CarrierMode::Own) {
            return new ShippingQuote(null, Money::fromCents($this->settings->own_carrier_fee_cents));
        }

        $zone = $this->zoneFor($city) ?? throw ShippingUnavailable::noZone();

        $rate = $zone->rates->first(fn (ShippingRate $rate): bool => $rate->covers($weightGrams))
            ?? throw ShippingUnavailable::noRate($weightGrams);

        return new ShippingQuote($zone, $rate->price_ht);
    }

    public function zoneFor(string $city): ?ShippingZone
    {
        $mapped = ShippingZoneCity::query()
            ->where('normalized', ShippingZoneCity::normalize($city))
            ->whereHas('zone', fn ($zone) => $zone->active())
            ->first();

        $zoneId = $mapped?->shipping_zone_id;

        return ShippingZone::query()
            ->active()
            ->with('rates')
            ->when($zoneId !== null, fn ($query) => $query->whereKey($zoneId), fn ($query) => $query->where('is_default', true))
            ->first();
    }
}
