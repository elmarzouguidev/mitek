<?php

namespace App\Services\Core\Product;

use App\Support\Money;
use Carbon\CarbonImmutable;

/**
 * A running promotion on the base unit price: the previous ("compare at") price and what it saves.
 */
final readonly class Promo
{
    public function __construct(
        public Money $compareAtHt,
        public Money $compareAtTtc,
        public Money $savingTtc,
        public int $percent,
        public ?CarbonImmutable $endsAt,
    ) {}
}
