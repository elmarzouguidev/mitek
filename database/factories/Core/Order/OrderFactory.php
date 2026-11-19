<?php

namespace Database\Factories\Core\Order;

use App\Enums\CarrierMode;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Models\Core\Order\Order;
use App\Models\Core\Order\OrderItem;
use App\Models\Core\Order\OrderStatusHistory;
use App\Models\Core\Product\Product;
use App\Support\Money;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'reference' => 'MO-'.strtoupper(fake()->unique()->bothify('??????-??????')),
            'user_id' => null,
            'status' => OrderStatus::PendingPayment,
            'payment_method' => PaymentMethod::BankTransfer,
            'contact_name' => fake()->name(),
            'contact_email' => fake()->safeEmail(),
            'contact_phone' => '0600000000',
            'shipping_city' => 'Casablanca',
            'shipping_address' => fake()->streetAddress(),
            'carrier_mode' => CarrierMode::Mitek,
            'total_weight_grams' => 1000,
            'subtotal_ht' => Money::fromDecimal(100),
            'shipping_ht' => Money::fromDecimal(25),
            'vat_total' => Money::fromDecimal(25),
            'total_ttc' => Money::fromDecimal(150),
        ];
    }

    public function status(OrderStatus $status): static
    {
        return $this->state(['status' => $status]);
    }

    public function method(PaymentMethod $method): static
    {
        return $this->state(['payment_method' => $method]);
    }

    public function totalling(string|int|float $ttc): static
    {
        return $this->state(['total_ttc' => Money::fromDecimal($ttc)]);
    }

    /**
     * Adds one line of the given product (snapshotting its current price) and the matching history entry.
     */
    public function withItem(?Product $product = null, int $quantity = 1): static
    {
        return $this->afterCreating(function (Order $order) use ($product, $quantity): void {
            $product ??= Product::factory()->create();

            OrderItem::create([
                'order_id' => $order->id,
                'product_id' => $product->id,
                'reference' => $product->reference,
                'name' => $product->name,
                'quantity' => $quantity,
                'unit_price_ht' => $product->price_ht,
                'vat_rate' => 20,
                'line_ht' => $product->price_ht->times($quantity),
                'line_vat' => $product->price_ht->times($quantity)->percentage(20),
                'line_ttc' => $product->price_ht->times($quantity)->plus($product->price_ht->times($quantity)->percentage(20)),
                'weight_grams' => $product->weight_grams,
            ]);
        });
    }

    public function withHistory(): static
    {
        return $this->afterCreating(fn (Order $order) => OrderStatusHistory::create([
            'order_id' => $order->id,
            'from_status' => null,
            'to_status' => $order->status,
            'note' => 'Commande créée.',
        ]));
    }
}
