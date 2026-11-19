<?php

use App\Enums\PaymentMethod;
use App\Services\Core\Order\PaymentRules;
use App\Settings\PaymentSettings;
use App\Support\Money;
use Illuminate\Validation\ValidationException;

function rules(): PaymentRules
{
    return app(PaymentRules::class);
}

it('allows cash on delivery up to 2,000 DH TTC, bound included', function () {
    expect(rules()->isAvailable(PaymentMethod::CashOnDelivery, Money::fromDecimal('1999.99')))->toBeTrue()
        ->and(rules()->isAvailable(PaymentMethod::CashOnDelivery, Money::fromDecimal('2000')))->toBeTrue()
        ->and(rules()->isAvailable(PaymentMethod::CashOnDelivery, Money::fromDecimal('2000.01')))->toBeFalse();
});

it('never restricts the other payment methods', function (PaymentMethod $method) {
    expect(rules()->isAvailable($method, Money::fromDecimal('999999')))->toBeTrue();
})->with([PaymentMethod::BankTransfer, PaymentMethod::CashDeposit, PaymentMethod::Cheque]);

it('lists the usable methods for a total', function () {
    expect(rules()->availableFor(Money::fromDecimal(500)))->toHaveCount(4)
        ->and(rules()->availableFor(Money::fromDecimal(5000)))->not->toContain(PaymentMethod::CashOnDelivery)
        ->and(rules()->availableFor(Money::fromDecimal(5000)))->toHaveCount(3);
});

it('follows the limit configured in the settings', function () {
    $settings = app(PaymentSettings::class);
    $settings->cod_limit_cents = 50000;
    $settings->save();

    expect(rules()->isAvailable(PaymentMethod::CashOnDelivery, Money::fromDecimal(501)))->toBeFalse()
        ->and(rules()->isAvailable(PaymentMethod::CashOnDelivery, Money::fromDecimal(500)))->toBeTrue();
});

it('raises a validation error naming the limit', function () {
    rules()->assertAvailable(PaymentMethod::CashOnDelivery, Money::fromDecimal(3000));
})->throws(ValidationException::class);
