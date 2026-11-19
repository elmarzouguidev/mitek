<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class ShippingSettings extends Settings
{
    /** Days between payment validation and shipment. */
    public int $dispatch_days;

    public string $delivery_estimate;

    /** Extra fee (centimes) when the customer uses their own carrier. */
    public int $own_carrier_fee_cents;

    public static function group(): string
    {
        return 'shipping';
    }
}
