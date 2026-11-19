<?php

namespace App\Services\Core\Cart;

use App\Enums\PaymentMethod;
use App\Models\Core\Product\Product;
use App\Services\Core\Order\PaymentRules;
use Illuminate\Support\Facades\Route;

/**
 * The cart as JSON for the mini-cart drawer. Every price comes from the cart summary (PriceCalculator),
 * the browser only displays what it is given.
 */
class CartPayload
{
    public function __construct(
        private readonly CartService $cart,
        private readonly PaymentRules $rules,
    ) {}

    /**
     * @param  Product|null  $added  the product that was just added, for the confirmation message
     * @return array<string, mixed>
     */
    public function make(?Product $added = null): array
    {
        $summary = $this->cart->summary();

        return [
            'count' => $summary->itemCount(),
            'lines' => $summary->lines->map(fn (CartLine $line): array => $this->line($line))->values()->all(),
            'subtotal_ht' => $summary->itemsHt()->format(),
            'vat' => $summary->itemsVat()->format(),
            'subtotal_ttc' => $summary->itemsTtc()->format(),
            'cod' => $this->codHint($summary),
            'adjustments' => $summary->adjustments,
            'added' => $added === null ? null : __('orders.drawer.added', ['name' => $added->name]),
            'urls' => [
                'cart' => route('cart.show'),
                'checkout' => route('checkout.show'),
                'quote' => Route::has('quotes.create') ? route('quotes.create') : null,
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function line(CartLine $line): array
    {
        $product = $line->product;

        return [
            'slug' => $product->slug,
            'url' => route('products.show', $product->slug),
            'name' => $product->name,
            'reference' => $product->reference,
            'image' => $product->getFirstMediaUrl(Product::GALLERY, 'thumb') ?: null,
            'quantity' => $line->quantity,
            'max' => $product->stock_quantity,
            'unit_ttc' => $line->quote->unitTtc->format(),
            'line_ttc' => $line->quote->lineTtc->format(),
            'tier_applied' => $line->quote->tierApplied,
            'tier_label' => $line->quote->tierApplied ? __('orders.cart.tier_applied') : null,
            'update_url' => route('cart.update', $product->slug),
            'remove_url' => route('cart.destroy', $product->slug),
        ];
    }

    /**
     * Cash on delivery is decided on the final total (shipping included), so above the limit with items
     * alone it is certainly unavailable, and below it the hint stays a hint.
     *
     * @return array{eligible: bool, limit: string, message: string}|null
     */
    private function codHint(CartSummary $summary): ?array
    {
        if ($summary->isEmpty()) {
            return null;
        }

        $limit = $this->rules->codLimit()->format();
        $eligible = $this->rules->isAvailable(PaymentMethod::CashOnDelivery, $summary->itemsTtc());

        return [
            'eligible' => $eligible,
            'limit' => $limit,
            'message' => $eligible
                ? __('orders.drawer.cod_available', ['limit' => $limit])
                : __('orders.drawer.cod_unavailable', ['limit' => $limit]),
        ];
    }
}
