<?php

namespace App\Enums;

enum PaymentStatus: string
{
    case Submitted = 'submitted';
    case Validated = 'validated';
    case Rejected = 'rejected';

    public function label(): string
    {
        return __('orders.payment_statuses.'.$this->value);
    }
}
