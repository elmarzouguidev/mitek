<?php

namespace App\Enums;

enum QuoteStatus: string
{
    case New = 'new';
    case Replied = 'replied';
    case Closed = 'closed';

    public function label(): string
    {
        return __('quotes.statuses.'.$this->value);
    }

    public function tone(): string
    {
        return match ($this) {
            self::New => 'amber',
            self::Replied => 'blue',
            self::Closed => 'slate',
        };
    }
}
