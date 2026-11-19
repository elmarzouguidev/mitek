<?php

namespace App\Enums;

enum CallbackSlot: string
{
    case Morning = 'morning';
    case Afternoon = 'afternoon';
    case Anytime = 'anytime';

    public function label(): string
    {
        return __('callback.slots.'.$this->value);
    }
}
