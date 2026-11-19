<?php

namespace App\Exceptions;

use RuntimeException;

class ShippingUnavailable extends RuntimeException
{
    public static function noZone(): self
    {
        return new self(__('orders.errors.shipping_no_zone'));
    }

    public static function noRate(int $weightGrams): self
    {
        return new self(__('orders.errors.shipping_no_rate', ['weight' => number_format($weightGrams / 1000, 1, ',', ' ')]));
    }
}
