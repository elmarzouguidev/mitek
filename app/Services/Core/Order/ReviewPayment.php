<?php

namespace App\Services\Core\Order;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Core\Order\OrderPayment;
use App\Models\User\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Manual validation of a submitted payment by an administrator.
 */
class ReviewPayment
{
    public function __construct(private readonly TransitionOrderStatus $transition) {}

    /**
     * Accepts the proof: the order becomes "paid".
     *
     * @throws ValidationException
     */
    public function validate(OrderPayment $payment, User $admin, ?string $note = null): OrderPayment
    {
        return $this->review($payment, $admin, PaymentStatus::Validated, OrderStatus::Paid, $note);
    }

    /**
     * Refuses the proof: the order goes back to "pending payment" so the customer can send another one.
     *
     * @throws ValidationException
     */
    public function reject(OrderPayment $payment, User $admin, string $note): OrderPayment
    {
        return $this->review($payment, $admin, PaymentStatus::Rejected, OrderStatus::PendingPayment, $note);
    }

    private function review(OrderPayment $payment, User $admin, PaymentStatus $result, OrderStatus $nextOrderStatus, ?string $note): OrderPayment
    {
        return DB::transaction(function () use ($payment, $admin, $result, $nextOrderStatus, $note): OrderPayment {
            $payment = OrderPayment::query()->lockForUpdate()->findOrFail($payment->id);

            if ($payment->status !== PaymentStatus::Submitted) {
                throw ValidationException::withMessages(['payment' => __('orders.errors.payment_already_reviewed')]);
            }

            $payment->update([
                'status' => $result,
                'reviewed_by' => $admin->id,
                'reviewed_at' => now(),
                'admin_note' => $note,
            ]);

            $this->transition->handle($payment->order()->firstOrFail(), $nextOrderStatus, $admin, $note);

            return $payment;
        });
    }
}
