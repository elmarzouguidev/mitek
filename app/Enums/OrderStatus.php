<?php

namespace App\Enums;

enum OrderStatus: string
{
    case PendingPayment = 'pending_payment';
    case PaymentSubmitted = 'payment_submitted';
    case Paid = 'paid';
    case Processing = 'processing';
    case Shipped = 'shipped';
    case Delivered = 'delivered';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return __('orders.statuses.'.$this->value);
    }

    /**
     * Colour family used by badges (storefront and back office).
     */
    public function tone(): string
    {
        return match ($this) {
            self::PendingPayment => 'amber',
            self::PaymentSubmitted => 'blue',
            self::Paid, self::Processing => 'blue',
            self::Shipped => 'blue',
            self::Delivered => 'green',
            self::Cancelled => 'red',
        };
    }

    /**
     * Legal next statuses. Cash on delivery has no payment to validate, so the admin confirms the
     * order straight from "pending payment" to "processing".
     *
     * @return array<int, self>
     */
    public function allowedNext(PaymentMethod $method): array
    {
        return match ($this) {
            self::PendingPayment => $method === PaymentMethod::CashOnDelivery
                ? [self::Processing, self::Cancelled]
                : [self::PaymentSubmitted, self::Cancelled],
            self::PaymentSubmitted => [self::Paid, self::PendingPayment, self::Cancelled],
            self::Paid => [self::Processing, self::Cancelled],
            self::Processing => [self::Shipped, self::Cancelled],
            self::Shipped => [self::Delivered],
            self::Delivered, self::Cancelled => [],
        };
    }

    public function canTransitionTo(self $next, PaymentMethod $method): bool
    {
        return in_array($next, $this->allowedNext($method), true);
    }

    /**
     * Orders whose stock was released or that no longer change.
     */
    public function isFinal(): bool
    {
        return in_array($this, [self::Delivered, self::Cancelled], true);
    }

    /**
     * Statuses that count as confirmed revenue.
     *
     * @return array<int, self>
     */
    public static function revenueStatuses(): array
    {
        return [self::Paid, self::Processing, self::Shipped, self::Delivered];
    }
}
