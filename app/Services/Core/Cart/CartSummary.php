<?php

namespace App\Services\Core\Cart;

use App\Support\Money;
use Illuminate\Support\Collection;

/**
 * Priced content of the cart. VAT is computed per line on the line total (see PriceCalculator).
 */
final readonly class CartSummary
{
    /**
     * @param  Collection<int, CartLine>  $lines
     * @param  array<int, string>  $adjustments  messages about lines removed or reduced since they were added
     */
    public function __construct(
        public Collection $lines,
        public array $adjustments = [],
    ) {}

    public function isEmpty(): bool
    {
        return $this->lines->isEmpty();
    }

    public function itemCount(): int
    {
        return $this->lines->sum(fn (CartLine $line): int => $line->quantity);
    }

    public function weightGrams(): int
    {
        return $this->lines->sum(fn (CartLine $line): int => $line->weightGrams());
    }

    public function itemsHt(): Money
    {
        return Money::fromCents($this->lines->sum(fn (CartLine $line): int => $line->quote->lineHt->cents));
    }

    public function itemsVat(): Money
    {
        return Money::fromCents($this->lines->sum(fn (CartLine $line): int => $line->quote->lineVat->cents));
    }

    public function itemsTtc(): Money
    {
        return $this->itemsHt()->plus($this->itemsVat());
    }
}
