<?php

use App\Enums\CarrierMode;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Exceptions\IllegalStatusTransition;
use App\Models\Core\Order\Order;
use App\Models\Core\Product\Product;
use App\Models\User\User;
use App\Services\Core\Order\TransitionOrderStatus;
use Illuminate\Validation\ValidationException;

function transition(Order $order, OrderStatus $to, ?User $by = null, array $attributes = []): Order
{
    return app(TransitionOrderStatus::class)->handle($order, $to, $by, null, $attributes);
}

it('follows the nominal flow for a prepaid order', function () {
    $admin = User::factory()->admin()->create();
    $order = Order::factory()->withItem()->withHistory()->create();

    foreach ([
        OrderStatus::PaymentSubmitted,
        OrderStatus::Paid,
        OrderStatus::Processing,
        OrderStatus::Shipped,
        OrderStatus::Delivered,
    ] as $status) {
        $order = transition($order, $status, $admin, ['tracking_number' => 'TRK-1']);
    }

    expect($order->status)->toBe(OrderStatus::Delivered)
        ->and($order->paid_at)->not->toBeNull()
        ->and($order->shipped_at)->not->toBeNull()
        ->and($order->delivered_at)->not->toBeNull()
        ->and($order->tracking_number)->toBe('TRK-1')
        ->and($order->history()->count())->toBe(6)
        ->and($order->history()->pluck('to_status')->map->value->all())->toBe(['pending_payment', 'payment_submitted', 'paid', 'processing', 'shipped', 'delivered']);
});

it('records who changed the status and when', function () {
    $admin = User::factory()->admin()->create();
    $order = Order::factory()->withItem()->create();

    app(TransitionOrderStatus::class)->handle($order, OrderStatus::PaymentSubmitted, $admin, 'Reçu par WhatsApp');

    $entry = $order->history()->firstOrFail();
    expect($entry->user_id)->toBe($admin->id)
        ->and($entry->note)->toBe('Reçu par WhatsApp')
        ->and($entry->from_status)->toBe(OrderStatus::PendingPayment)
        ->and($entry->to_status)->toBe(OrderStatus::PaymentSubmitted);
});

it('allows only the legal transitions', function (OrderStatus $from, OrderStatus $to, bool $allowed) {
    $order = Order::factory()->status($from)->withItem()->create();

    if ($allowed) {
        expect(transition($order, $to, null, ['tracking_number' => 'T'])->status)->toBe($to);
    } else {
        expect(fn () => transition($order, $to))->toThrow(IllegalStatusTransition::class);
    }
})->with([
    'pending to submitted' => [OrderStatus::PendingPayment, OrderStatus::PaymentSubmitted, true],
    'pending to cancelled' => [OrderStatus::PendingPayment, OrderStatus::Cancelled, true],
    'pending to paid' => [OrderStatus::PendingPayment, OrderStatus::Paid, false],
    'pending to processing' => [OrderStatus::PendingPayment, OrderStatus::Processing, false],
    'pending to shipped' => [OrderStatus::PendingPayment, OrderStatus::Shipped, false],
    'submitted to paid' => [OrderStatus::PaymentSubmitted, OrderStatus::Paid, true],
    'submitted back to pending' => [OrderStatus::PaymentSubmitted, OrderStatus::PendingPayment, true],
    'submitted to processing' => [OrderStatus::PaymentSubmitted, OrderStatus::Processing, false],
    'paid to processing' => [OrderStatus::Paid, OrderStatus::Processing, true],
    'paid to shipped' => [OrderStatus::Paid, OrderStatus::Shipped, false],
    'paid to cancelled' => [OrderStatus::Paid, OrderStatus::Cancelled, true],
    'processing to shipped' => [OrderStatus::Processing, OrderStatus::Shipped, true],
    'processing to delivered' => [OrderStatus::Processing, OrderStatus::Delivered, false],
    'shipped to delivered' => [OrderStatus::Shipped, OrderStatus::Delivered, true],
    'shipped to cancelled' => [OrderStatus::Shipped, OrderStatus::Cancelled, false],
    'shipped back to paid' => [OrderStatus::Shipped, OrderStatus::Paid, false],
    'delivered to anything' => [OrderStatus::Delivered, OrderStatus::Processing, false],
    'delivered to cancelled' => [OrderStatus::Delivered, OrderStatus::Cancelled, false],
    'cancelled to pending' => [OrderStatus::Cancelled, OrderStatus::PendingPayment, false],
    'cancelled to paid' => [OrderStatus::Cancelled, OrderStatus::Paid, false],
]);

it('leaves no history entry behind when a transition is refused', function () {
    $order = Order::factory()->withItem()->withHistory()->create();

    expect(fn () => transition($order, OrderStatus::Delivered))->toThrow(IllegalStatusTransition::class)
        ->and($order->fresh()->status)->toBe(OrderStatus::PendingPayment)
        ->and($order->history()->count())->toBe(1);
});

it('lets a cash on delivery order skip the payment steps', function () {
    $order = Order::factory()->method(PaymentMethod::CashOnDelivery)->withItem()->create();

    expect(transition($order, OrderStatus::Processing)->status)->toBe(OrderStatus::Processing);
    expect(fn () => transition(Order::factory()->method(PaymentMethod::CashOnDelivery)->create(), OrderStatus::PaymentSubmitted))
        ->toThrow(IllegalStatusTransition::class);
});

it('records the cash collected when a cash on delivery order is delivered', function () {
    $admin = User::factory()->admin()->create();
    $order = Order::factory()->method(PaymentMethod::CashOnDelivery)->withItem()->create();

    foreach ([OrderStatus::Processing, OrderStatus::Shipped, OrderStatus::Delivered] as $status) {
        $order = transition($order, $status, $admin, ['tracking_number' => 'T-9']);
    }

    $payment = $order->payments()->firstOrFail();
    expect($order->paid_at)->not->toBeNull()
        ->and($payment->status)->toBe(PaymentStatus::Validated)
        ->and($payment->amount->cents)->toBe($order->total_ttc->cents)
        ->and($payment->reviewed_by)->toBe($admin->id);
});

it('requires a tracking number to ship an order delivered by Mitek', function () {
    $order = Order::factory()->status(OrderStatus::Processing)->withItem()->create();

    expect(fn () => transition($order, OrderStatus::Shipped))->toThrow(ValidationException::class)
        ->and(fn () => transition($order, OrderStatus::Shipped, null, ['tracking_number' => '  ']))->toThrow(ValidationException::class)
        ->and($order->fresh()->status)->toBe(OrderStatus::Processing);
});

it('does not require a tracking number when the customer uses their own carrier', function () {
    $order = Order::factory()->status(OrderStatus::Processing)->withItem()->create(['carrier_mode' => CarrierMode::Own]);

    expect(transition($order, OrderStatus::Shipped)->status)->toBe(OrderStatus::Shipped);
});

it('puts the stock back when an order is cancelled', function () {
    $product = Product::factory()->create(['stock_quantity' => 10]);
    $order = Order::factory()->withItem($product, 4)->create();
    $product->update(['stock_quantity' => 6]); // as after checkout

    $order = transition($order, OrderStatus::Cancelled);

    expect($product->fresh()->stock_quantity)->toBe(10)
        ->and($order->cancelled_at)->not->toBeNull();
});

it('puts the stock back even when the product was deleted meanwhile', function () {
    $product = Product::factory()->create(['stock_quantity' => 6]);
    $order = Order::factory()->withItem($product, 4)->create();
    $product->delete();

    transition($order, OrderStatus::Cancelled);

    expect(Product::withTrashed()->find($product->id)->stock_quantity)->toBe(10);
});

it('does not restock twice', function () {
    $product = Product::factory()->create(['stock_quantity' => 6]);
    $order = Order::factory()->withItem($product, 4)->create();

    transition($order, OrderStatus::Cancelled);

    expect(fn () => transition($order, OrderStatus::Cancelled))->toThrow(IllegalStatusTransition::class)
        ->and($product->fresh()->stock_quantity)->toBe(10);
});

it('exposes the next legal statuses per payment method', function () {
    expect(OrderStatus::PendingPayment->allowedNext(PaymentMethod::BankTransfer))->toBe([OrderStatus::PaymentSubmitted, OrderStatus::Cancelled])
        ->and(OrderStatus::PendingPayment->allowedNext(PaymentMethod::CashOnDelivery))->toBe([OrderStatus::Processing, OrderStatus::Cancelled])
        ->and(OrderStatus::Delivered->allowedNext(PaymentMethod::Cheque))->toBe([])
        ->and(OrderStatus::Cancelled->isFinal())->toBeTrue()
        ->and(OrderStatus::Paid->isFinal())->toBeFalse();
});
