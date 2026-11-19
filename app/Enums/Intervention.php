<?php

namespace App\Enums;

enum Intervention: string
{
    case OnSite = 'on_site';
    case Remote = 'remote';

    public function label(): string
    {
        return __('services.interventions.'.$this->value);
    }
}
