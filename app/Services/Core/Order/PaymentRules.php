<?php

namespace App\Services\Core\Order;

use App\Enums\PaymentMethod;
use App\Settings\PaymentSettings;
use App\Support\Money;
use Illuminate\Validation\ValidationException;

/**
 * Which payment methods an order can use. Cash on delivery is only offered up to the limit
 * (2,000 DH TTC by default, shipping included) set in the payment settings.
 */
class PaymentRules
{
    public function __construct(private readonly PaymentSettings $settings) {}

    public function codLimit(): Money
    {
        return Money::fromCents($this->settings->cod_limit_cents);
    }

    public function isAvailable(PaymentMethod $method, Money $totalTtc): bool
    {
        return $method !== PaymentMethod::CashOnDelivery || $totalTtc->isLessThanOrEqualTo($this->codLimit());
    }

    /**
     * @return array<int, PaymentMethod>
     */
    public function availableFor(Money $totalTtc): array
    {
        return array_values(array_filter(
            PaymentMethod::cases(),
            fn (PaymentMethod $method): bool => $this->isAvailable($method, $totalTtc),
        ));
    }

    /**
     * @throws ValidationException
     */
    public function assertAvailable(PaymentMethod $method, Money $totalTtc): void
    {
        if (! $this->isAvailable($method, $totalTtc)) {
            throw ValidationException::withMessages([
                'payment_method' => __('orders.errors.cod_limit', ['limit' => $this->codLimit()->format()]),
            ]);
        }
    }
}
