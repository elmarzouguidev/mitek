<?php

namespace App\Services\Core\Order;

use App\Enums\CarrierMode;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Events\OrderStatusChanged;
use App\Exceptions\IllegalStatusTransition;
use App\Models\Core\Order\Order;
use App\Models\Core\Order\OrderStatusHistory;
use App\Models\Core\Product\Product;
use App\Models\User\User;
use App\Services\Core\Invoice\IssueCreditNote;
use App\Services\Core\Invoice\IssueInvoice;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * The only place where an order changes status: it enforces the legal transitions, stamps the
 * dates, releases the stock on cancellation and writes the status history.
 */
class TransitionOrderStatus
{
    public function __construct(
        private readonly IssueInvoice $invoices,
        private readonly IssueCreditNote $creditNotes,
    ) {}

    /**
     * @param  array{tracking_number?: string|null, tracking_carrier?: string|null}  $attributes
     *
     * @throws IllegalStatusTransition
     * @throws ValidationException
     */
    public function handle(Order $order, OrderStatus $to, ?User $by = null, ?string $note = null, array $attributes = []): Order
    {
        [$order, $from] = DB::transaction(function () use ($order, $to, $by, $note, $attributes): array {
            $order = Order::query()->lockForUpdate()->findOrFail($order->id);
            $from = $order->status;

            if (! $from->canTransitionTo($to, $order->payment_method)) {
                throw IllegalStatusTransition::between($from, $to);
            }

            if ($to === OrderStatus::Shipped && $order->carrier_mode === CarrierMode::Mitek && blank($attributes['tracking_number'] ?? null)) {
                throw ValidationException::withMessages(['tracking_number' => __('orders.errors.tracking_required')]);
            }

            $order->status = $to;
            $order->fill(array_filter($attributes, fn (mixed $value): bool => filled($value)));
            $this->stamp($order, $to);
            $order->save();

            if ($to === OrderStatus::Cancelled) {
                $this->restock($order);
                $this->creditNotes->handle($order);
            }

            if ($to === OrderStatus::Delivered && $order->isCashOnDelivery()) {
                $this->recordCashCollected($order, $by);
            }

            if ($to === OrderStatus::Paid || ($to === OrderStatus::Processing && $order->isCashOnDelivery())) {
                $this->invoices->handle($order);
            }

            OrderStatusHistory::create([
                'order_id' => $order->id,
                'from_status' => $from,
                'to_status' => $to,
                'user_id' => $by?->id,
                'note' => $note,
            ]);

            return [$order, $from];
        });

        OrderStatusChanged::dispatch($order, $from, $to, $note);

        return $order;
    }

    private function stamp(Order $order, OrderStatus $to): void
    {
        match ($to) {
            OrderStatus::Paid => $order->paid_at = now(),
            OrderStatus::Shipped => $order->shipped_at = now(),
            OrderStatus::Delivered => $order->delivered_at = now(),
            OrderStatus::Cancelled => $order->cancelled_at = now(),
            default => null,
        };
    }

    private function restock(Order $order): void
    {
        foreach ($order->items()->get() as $item) {
            if ($item->product_id !== null) {
                Product::withTrashed()->whereKey($item->product_id)->increment('stock_quantity', $item->quantity);
            }
        }
    }

    /**
     * Cash on delivery is paid when the parcel is handed over: record it as a validated payment.
     */
    private function recordCashCollected(Order $order, ?User $by): void
    {
        $order->paid_at ??= now();
        $order->save();

        $order->payments()->create([
            'method' => $order->payment_method,
            'amount' => $order->total_ttc,
            'status' => PaymentStatus::Validated,
            'reviewed_by' => $by?->id,
            'reviewed_at' => now(),
        ]);
    }
}
