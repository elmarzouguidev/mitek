<?php

namespace App\Enums;

enum CarrierMode: string
{
    case Mitek = 'mitek';
    case Own = 'own';

    public function label(): string
    {
        return __('orders.carrier_modes.'.$this->value);
    }
}
