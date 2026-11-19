<?php

use App\Casts\MoneyCast;
use App\Models\Core\Product\Product;
use App\Support\Money;

it('stores dirham decimals as integer centimes', function (string|int|float $input, int $cents) {
    expect(Money::fromDecimal($input)->cents)->toBe($cents);
})->with([
    'integer' => [12, 1200],
    'float' => [19.99, 1999],
    'french string' => ['1 250,50', 125050],
    'dot string' => ['0.05', 5],
]);

it('rejects non numeric amounts', function () {
    Money::fromDecimal('abc');
})->throws(InvalidArgumentException::class);

it('does arithmetic without floating point drift', function () {
    $price = Money::fromCents(1999);

    expect($price->times(3)->cents)->toBe(5997)
        ->and($price->plus(Money::fromCents(1))->cents)->toBe(2000)
        ->and($price->minus(Money::fromCents(999))->cents)->toBe(1000)
        ->and($price->percentage(20)->cents)->toBe(400);
});

it('formats amounts in French with the dirham sign', function () {
    expect(Money::fromCents(125050)->format())->toBe("1\u{202F}250,50\u{00A0}DH")
        ->and((string) Money::zero())->toBe("0,00\u{00A0}DH");
});

it('compares against the cash on delivery limit', function () {
    $limit = Money::fromCents(200000);

    expect(Money::fromCents(200000)->isLessThanOrEqualTo($limit))->toBeTrue()
        ->and(Money::fromCents(200001)->isGreaterThan($limit))->toBeTrue();
});

it('casts model attributes between integers and Money', function () {
    $cast = new MoneyCast;
    $model = new Product;

    expect($cast->get($model, 'price_ht', '12345', []))->toEqual(Money::fromCents(12345))
        ->and($cast->get($model, 'price_ht', null, []))->toBeNull()
        ->and($cast->set($model, 'price_ht', Money::fromCents(500), []))->toBe(500)
        ->and($cast->set($model, 'price_ht', 700, []))->toBe(700);
});

it('refuses ambiguous float values in the cast', function () {
    (new MoneyCast)->set(new Product, 'price_ht', 12.5, []);
})->throws(InvalidArgumentException::class);
