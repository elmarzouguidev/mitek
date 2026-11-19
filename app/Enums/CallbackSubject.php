<?php

namespace App\Enums;

enum CallbackSubject: string
{
    case Order = 'order';
    case Quote = 'quote';
    case Product = 'product';
    case Delivery = 'delivery';
    case Service = 'service';
    case Other = 'other';

    public function label(): string
    {
        return __('callback.subjects.'.$this->value);
    }
}
