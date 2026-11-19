<?php

namespace App\Enums;

enum CallbackStatus: string
{
    case New = 'new';
    case Called = 'called';
    case Closed = 'closed';

    public function label(): string
    {
        return __('callback.statuses.'.$this->value);
    }

    public function tone(): string
    {
        return match ($this) {
            self::New => 'amber',
            self::Called => 'green',
            self::Closed => 'slate',
        };
    }
}
