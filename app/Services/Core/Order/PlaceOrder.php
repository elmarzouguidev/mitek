<?php

namespace App\Services\Core\Order;

use App\Enums\CarrierMode;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Events\OrderPlaced;
use App\Exceptions\ShippingUnavailable;
use App\Http\Requests\CheckoutRequest;
use App\Models\Core\Order\Order;
use App\Models\Core\Order\OrderStatusHistory;
use App\Models\Core\Product\Product;
use App\Models\User\User;
use App\Services\Core\Cart\CartLine;
use App\Services\Core\Cart\CartService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Turns the cart into an order. Everything is re-checked inside one transaction (stock under row
 * locks, shipping, payment method), so a stale checkout page can never create an invalid order.
 */
class PlaceOrder
{
    private const REFERENCE_ATTEMPTS = 5;

    public function __construct(
        private readonly CartService $cart,
        private readonly CheckoutPricing $pricing,
        private readonly PaymentRules $paymentRules,
        private readonly OrderReferenceGenerator $references,
    ) {}

    /**
     * @param  array<string, mixed>  $data  validated {@see CheckoutRequest} input
     *
     * @throws ValidationException
     */
    public function handle(array $data, ?User $user = null): Order
    {
        $order = DB::transaction(function () use ($data, $user): Order {
            $summary = $this->cart->summary();

            if ($summary->isEmpty()) {
                throw ValidationException::withMessages(['cart' => $summary->adjustments !== [] ? implode(' ', $summary->adjustments) : __('orders.errors.empty_cart')]);
            }

            if ($summary->adjustments !== []) {
                throw ValidationException::withMessages(['cart' => implode(' ', $summary->adjustments)]);
            }

            $this->lockAndCheckStock($summary->lines->all());

            $carrierMode = CarrierMode::from($data['carrier_mode']);
            $method = PaymentMethod::from($data['payment_method']);

            try {
                $quote = $this->pricing->quote($summary, $data['shipping_city'], $carrierMode);
            } catch (ShippingUnavailable $exception) {
                throw ValidationException::withMessages(['shipping_city' => $exception->getMessage()]);
            }

            $this->paymentRules->assertAvailable($method, $quote->totalTtc);

            $order = Order::create([
                'reference' => $this->uniqueReference(),
                'user_id' => $user?->id,
                'status' => OrderStatus::PendingPayment,
                'payment_method' => $method,
                'contact_name' => $data['contact_name'],
                'contact_email' => $data['contact_email'],
                'contact_phone' => $data['contact_phone'],
                'company_name' => $data['company_name'] ?? null,
                'ice' => $data['ice'] ?? null,
                'if_number' => $data['if_number'] ?? null,
                'rc_number' => $data['rc_number'] ?? null,
                'shipping_city' => $data['shipping_city'],
                'shipping_address' => $data['shipping_address'],
                'shipping_postal_code' => $data['shipping_postal_code'] ?? null,
                'carrier_mode' => $carrierMode,
                'own_carrier_note' => $carrierMode === CarrierMode::Own ? ($data['own_carrier_note'] ?? null) : null,
                'shipping_zone_id' => $quote->zone?->id,
                'shipping_zone_name' => $quote->zone?->name,
                'total_weight_grams' => $quote->weightGrams,
                'subtotal_ht' => $quote->itemsHt,
                'shipping_ht' => $quote->shippingHt,
                'vat_total' => $quote->vatTotal,
                'total_ttc' => $quote->totalTtc,
                'customer_note' => $data['customer_note'] ?? null,
            ]);

            foreach ($summary->lines as $line) {
                $order->items()->create([
                    'product_id' => $line->product->id,
                    'reference' => $line->product->reference,
                    'name' => $line->product->name,
                    'quantity' => $line->quantity,
                    'unit_price_ht' => $line->quote->unitHt,
                    'vat_rate' => $line->quote->vatRate,
                    'line_ht' => $line->quote->lineHt,
                    'line_vat' => $line->quote->lineVat,
                    'line_ttc' => $line->quote->lineTtc,
                    'weight_grams' => $line->product->weight_grams,
                ]);

                Product::whereKey($line->product->id)->decrement('stock_quantity', $line->quantity);
            }

            OrderStatusHistory::create([
                'order_id' => $order->id,
                'from_status' => null,
                'to_status' => OrderStatus::PendingPayment,
                'user_id' => $user?->id,
                'note' => __('orders.history.created'),
            ]);

            return $order;
        });

        $this->cart->clear();

        OrderPlaced::dispatch($order);

        return $order;
    }

    /**
     * @param  array<int, CartLine>  $lines
     *
     * @throws ValidationException
     */
    private function lockAndCheckStock(array $lines): void
    {
        $locked = Product::query()
            ->whereKey(array_map(fn (CartLine $line): int => $line->product->id, $lines))
            ->lockForUpdate()
            ->get()
            ->keyBy('id');

        foreach ($lines as $line) {
            $stock = $locked->get($line->product->id)?->stock_quantity ?? 0;

            if ($stock < $line->quantity) {
                throw ValidationException::withMessages([
                    'cart' => __('orders.errors.stock_limit', ['name' => $line->product->name, 'available' => $stock]),
                ]);
            }
        }
    }

    private function uniqueReference(): string
    {
        for ($attempt = 0; $attempt < self::REFERENCE_ATTEMPTS; $attempt++) {
            $reference = $this->references->generate();

            if (! Order::where('reference', $reference)->exists()) {
                return $reference;
            }
        }

        throw new \RuntimeException('Unable to generate a unique order reference.');
    }
}
