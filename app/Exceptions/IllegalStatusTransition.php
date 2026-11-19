<?php

namespace App\Exceptions;

use App\Enums\OrderStatus;
use DomainException;

class IllegalStatusTransition extends DomainException
{
    public static function between(OrderStatus $from, OrderStatus $to): self
    {
        return new self(__('orders.errors.illegal_transition', ['from' => $from->label(), 'to' => $to->label()]));
    }
}
