<?php

namespace App\Enums;

enum Urgency: string
{
    case Planned = 'planned';
    case Soon = 'soon';
    case Urgent = 'urgent';

    public function label(): string
    {
        return __('services.urgencies.'.$this->value);
    }
}
