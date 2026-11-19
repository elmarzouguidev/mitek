<?php

namespace App\Listeners;

use App\Enums\OrderStatus;
use App\Events\OrderStatusChanged;
use App\Mail\OrderCancelledMail;
use App\Mail\OrderShippedMail;
use App\Mail\PaymentProofReceivedMail;
use App\Mail\PaymentRejectedMail;
use App\Mail\PaymentValidatedMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Mail;

/**
 * One email per milestone the customer cares about: proof received, payment validated (or order
 * confirmed for cash on delivery), proof rejected, order shipped, order cancelled (with the credit note when there is one).
 */
class SendOrderStatusEmail implements ShouldQueue
{
    public bool $afterCommit = true;

    public function handle(OrderStatusChanged $event): void
    {
        $order = $event->order;

        $mail = match (true) {
            $event->to === OrderStatus::PaymentSubmitted => new PaymentProofReceivedMail($order),
            $event->to === OrderStatus::Paid => new PaymentValidatedMail($order),
            $event->to === OrderStatus::Processing && $order->isCashOnDelivery() => new PaymentValidatedMail($order),
            $event->to === OrderStatus::PendingPayment && $event->from === OrderStatus::PaymentSubmitted => new PaymentRejectedMail($order, (string) $event->note),
            $event->to === OrderStatus::Shipped => new OrderShippedMail($order),
            $event->to === OrderStatus::Cancelled => new OrderCancelledMail($order),
            default => null,
        };

        if ($mail instanceof Mailable) {
            Mail::to($order->contact_email)->send($mail);
        }
    }
}
