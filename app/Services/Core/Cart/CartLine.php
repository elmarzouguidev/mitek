<?php

namespace App\Services\Core\Cart;

use App\Models\Core\Product\Product;
use App\Services\Core\Product\PriceQuote;

final readonly class CartLine
{
    public function __construct(
        public Product $product,
        public int $quantity,
        public PriceQuote $quote,
    ) {}

    public function weightGrams(): int
    {
        return $this->product->weight_grams * $this->quantity;
    }
}
