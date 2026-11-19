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

    /**
     * ISO weekdays (1 = Monday … 7 = Sunday) on which orders are prepared and parcels delivered.
     *
     * @var array<int, int>
     */
    public array $working_days;

    /**
     * Non-working dates (Y-m-d) skipped by the delivery estimate.
     *
     * @var array<int, string>
     */
    public array $holidays;

    public static function group(): string
    {
        return 'shipping';
    }
}
