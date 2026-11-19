<?php

namespace App\Enums;

enum UserRole: string
{
    case Admin = 'admin';
    case Customer = 'customer';

    public function label(): string
    {
        return __('roles.names.'.$this->value);
    }
}
