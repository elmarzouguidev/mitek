<?php

namespace App\Services\Core\Cart;

use App\Models\Core\Product\Product;
use App\Services\Core\Product\PriceCalculator;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\ValidationException;

/**
 * Session-backed cart: a map of product id => quantity. Prices are never stored, they are always
 * recomputed from the catalogue (tiers, VAT) so a stale cart can't keep an old price.
 */
class CartService
{
    public const SESSION_KEY = 'cart';

    public const MAX_QUANTITY_PER_LINE = 10000;

    public function __construct(private readonly PriceCalculator $prices) {}

    /**
     * Adds to the quantity already in the cart.
     *
     * @throws ValidationException when the product is unavailable or the stock is insufficient
     */
    public function add(Product $product, int $quantity): void
    {
        $this->set($product, ($this->items()[$product->id] ?? 0) + $quantity);
    }

    /**
     * Sets the quantity of a line; zero or less removes it.
     *
     * @throws ValidationException
     */
    public function set(Product $product, int $quantity): void
    {
        if ($quantity <= 0) {
            $this->remove($product->id);

            return;
        }

        if (! $product->is_active || ! $product->isInStock()) {
            throw ValidationException::withMessages(['quantite' => __('orders.errors.out_of_stock', ['name' => $product->name])]);
        }

        if ($quantity > $product->stock_quantity) {
            throw ValidationException::withMessages(['quantite' => __('orders.errors.stock_limit', ['name' => $product->name, 'available' => $product->stock_quantity])]);
        }

        $items = $this->items();
        $items[$product->id] = min($quantity, self::MAX_QUANTITY_PER_LINE);
        $this->store($items);
    }

    public function remove(int $productId): void
    {
        $items = $this->items();
        unset($items[$productId]);
        $this->store($items);
    }

    public function clear(): void
    {
        Session::forget(self::SESSION_KEY);
    }

    /**
     * Number of items (sum of quantities), without touching the database.
     */
    public function count(): int
    {
        return array_sum($this->items());
    }

    /**
     * Prices the cart from the catalogue. Lines whose product disappeared, was deactivated or sold
     * out are dropped, and quantities above the stock are reduced; each change is reported.
     */
    public function summary(): CartSummary
    {
        $items = $this->items();

        if ($items === []) {
            return new CartSummary(collect());
        }

        $products = Product::query()
            ->visible()
            ->with(['media', 'brand', 'priceTiers'])
            ->whereKey(array_keys($items))
            ->get()
            ->keyBy('id');

        $unavailableNames = Product::withTrashed()
            ->whereKey(array_diff(array_keys($items), $products->keys()->all()))
            ->pluck('name', 'id');

        $lines = collect();
        $adjustments = [];
        $cleaned = [];

        foreach ($items as $productId => $quantity) {
            $product = $products->get($productId);

            if ($product === null || ! $product->isInStock()) {
                $adjustments[] = __('orders.errors.line_removed', ['name' => $product?->name ?? $unavailableNames->get($productId, __('orders.errors.unknown_product'))]);

                continue;
            }

            if ($quantity > $product->stock_quantity) {
                $adjustments[] = __('orders.errors.line_reduced', ['name' => $product->name, 'available' => $product->stock_quantity]);
                $quantity = $product->stock_quantity;
            }

            $cleaned[$productId] = $quantity;
            $lines->push(new CartLine($product, $quantity, $this->prices->quote($product, $quantity)));
        }

        if ($cleaned !== $items) {
            $this->store($cleaned);
        }

        return new CartSummary($lines, $adjustments);
    }

    /**
     * @return array<int, int>
     */
    private function items(): array
    {
        return collect(Session::get(self::SESSION_KEY, []))
            ->mapWithKeys(fn (mixed $quantity, mixed $productId): array => [(int) $productId => (int) $quantity])
            ->filter(fn (int $quantity): bool => $quantity > 0)
            ->all();
    }

    /**
     * @param  array<int, int>  $items
     */
    private function store(array $items): void
    {
        $items === [] ? Session::forget(self::SESSION_KEY) : Session::put(self::SESSION_KEY, $items);
    }
}
