<?php

namespace App\Enums;

enum ServiceRequestStatus: string
{
    case New = 'new';
    case Contacted = 'contacted';
    case QuoteSent = 'quote_sent';
    case Done = 'done';
    case Closed = 'closed';

    public function label(): string
    {
        return __('services.statuses.'.$this->value);
    }

    public function tone(): string
    {
        return match ($this) {
            self::New => 'amber',
            self::Contacted, self::QuoteSent => 'blue',
            self::Done => 'green',
            self::Closed => 'slate',
        };
    }
}
