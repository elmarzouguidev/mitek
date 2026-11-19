<?php

namespace App\Services\Core\Order;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Core\Order\Order;
use App\Models\Core\Order\OrderPayment;
use App\Models\User\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Records the proof of payment sent by the customer (one or several files, e.g. recto and verso)
 * and moves the order to "payment submitted". An administrator then validates it by hand.
 */
class SubmitPaymentProof
{
    public function __construct(private readonly TransitionOrderStatus $transition) {}

    /**
     * @param  array<int, UploadedFile>  $files
     *
     * @throws ValidationException
     */
    public function handle(Order $order, array $files, ?User $by = null): OrderPayment
    {
        return DB::transaction(function () use ($order, $files, $by): OrderPayment {
            $order = Order::query()->lockForUpdate()->findOrFail($order->id);

            if (! $order->acceptsProof()) {
                throw ValidationException::withMessages(['proof' => __('orders.errors.proof_not_accepted')]);
            }

            $payment = $order->payments()->create([
                'method' => $order->payment_method,
                'amount' => $order->total_ttc,
                'status' => PaymentStatus::Submitted,
            ]);

            foreach ($files as $file) {
                $payment->addMedia($file)->toMediaCollection(OrderPayment::PROOF);
            }

            $this->transition->handle($order, OrderStatus::PaymentSubmitted, $by, __('orders.history.proof_received'));

            return $payment;
        });
    }
}
