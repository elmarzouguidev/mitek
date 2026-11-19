<?php

namespace App\Services\Core\Shipping;

use App\Models\Core\Shipping\ShippingZone;
use App\Support\Money;

final readonly class ShippingQuote
{
    public function __construct(public ?ShippingZone $zone, public Money $priceHt) {}
}
