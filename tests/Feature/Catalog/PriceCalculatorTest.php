<?php

use App\Models\Core\Product\Product;
use App\Services\Core\Product\PriceCalculator;
use App\Settings\GeneralSettings;
use App\Support\Money;

function calculator(): PriceCalculator
{
    return app(PriceCalculator::class);
}

function productWithTiers(string|int|float $price, array $tiers = [], array $attributes = []): Product
{
    return Product::factory()->priced($price)->withTiers($tiers)->create($attributes)->load('priceTiers');
}

it('uses the base price when the product has no tiers', function () {
    $product = productWithTiers(100);

    expect(calculator()->unitPriceHt($product, 1)->cents)->toBe(10000)
        ->and(calculator()->unitPriceHt($product, 500)->cents)->toBe(10000);
});

it('resolves the tier of the highest reached minimum quantity', function (int $quantity, int $expectedCents) {
    $product = productWithTiers(100, [3 => 90, 10 => 80, 50 => 70]);

    expect(calculator()->unitPriceHt($product, $quantity)->cents)->toBe($expectedCents);
})->with([
    'below the first tier' => [1, 10000],
    'just below the first tier' => [2, 10000],
    'first tier boundary' => [3, 9000],
    'between tiers' => [9, 9000],
    'second tier boundary' => [10, 8000],
    'between second and third' => [49, 8000],
    'last tier boundary' => [50, 7000],
    'far above the last tier' => [5000, 7000],
]);

it('is not fooled by the order in which the tiers were created', function () {
    $product = productWithTiers(100, [50 => 70, 3 => 90, 10 => 80]);

    expect(calculator()->unitPriceHt($product, 12)->cents)->toBe(8000);
});

it('treats a quantity below one as one', function () {
    $product = productWithTiers(100, [3 => 90]);

    expect(calculator()->quote($product, 0)->quantity)->toBe(1)
        ->and(calculator()->quote($product, -4)->unitHt->cents)->toBe(10000);
});

it('adds the default VAT from the settings to the HT price', function () {
    $product = productWithTiers('790');

    $quote = calculator()->quote($product, 1);

    expect($quote->vatRate)->toBe(20)
        ->and($quote->unitTtc->cents)->toBe(94800)
        ->and($quote->lineVat->cents)->toBe(15800);
});

it('follows a change of the default VAT rate', function () {
    $settings = app(GeneralSettings::class);
    $settings->vat_rate = 10;
    $settings->save();

    expect(calculator()->unitPriceTtc(productWithTiers(100))->cents)->toBe(11000);
});

it('prefers the product own VAT rate over the default', function () {
    $product = productWithTiers(100, [], ['vat_rate' => 7]);

    expect(calculator()->vatRate($product))->toBe(7)
        ->and(calculator()->unitPriceTtc($product)->cents)->toBe(10700);
});

it('rounds VAT half up to the centime', function (int $htCents, int $vatRate, int $expectedTtcCents) {
    expect(calculator()->withVat(Money::fromCents($htCents), $vatRate)->cents)->toBe($expectedTtcCents);
})->with([
    'rounds down below half' => [7, 20, 8],     // 1.4 -> 1
    'rounds up above half' => [13, 20, 16],     // 2.6 -> 3
    'rounds half up' => [10, 5, 11],            // 0.5 -> 1
    'exact amounts untouched' => [1000, 20, 1200],
    'zero rate' => [1234, 0, 1234],
]);

it('computes the VAT of a line on the line total, not unit by unit', function () {
    $product = productWithTiers('0.13');

    $quote = calculator()->quote($product, 10);

    expect($quote->unitTtc->cents)->toBe(16)
        ->and($quote->lineHt->cents)->toBe(130)
        ->and($quote->lineVat->cents)->toBe(26)
        ->and($quote->lineTtc->cents)->toBe(156);
});

it('flags the quote when a tier is applied', function () {
    $product = productWithTiers(100, [5 => 90]);

    expect(calculator()->quote($product, 4)->tierApplied)->toBeFalse()
        ->and(calculator()->quote($product, 5)->tierApplied)->toBeTrue();
});

it('builds the tier table with contiguous quantity ranges', function () {
    $product = productWithTiers(100, [5 => 90, 20 => 80]);

    $table = calculator()->tierTable($product);

    expect($table)->toHaveCount(3)
        ->and(collect($table)->map(fn (array $row) => [$row['from'], $row['to']])->all())->toBe([[1, 4], [5, 19], [20, null]])
        ->and($table[1]['unit_ht']->cents)->toBe(9000)
        ->and($table[1]['unit_ttc']->cents)->toBe(10800);
});

it('returns no tier table for a product without tiers', function () {
    expect(calculator()->tierTable(productWithTiers(100)))->toBe([]);
});

it('serves the same quote as JSON for the storefront price box', function () {
    $product = productWithTiers(100, [10 => 80]);

    $this->getJson(route('products.price', [$product->slug, 'quantite' => 12]))
        ->assertOk()
        ->assertJsonPath('quantity', 12)
        ->assertJsonPath('unit_ht.cents', 8000)
        ->assertJsonPath('unit_ttc.formatted', Money::fromDecimal(96)->format())
        ->assertJsonPath('line_ttc.cents', 115200)
        ->assertJsonPath('tier_applied', true);
});

it('rejects invalid quantities on the price endpoint', function (mixed $quantity) {
    $product = productWithTiers(100);

    $this->getJson(route('products.price', [$product->slug, 'quantite' => $quantity]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors('quantite');
})->with(['zero' => 0, 'negative' => -3, 'text' => 'abc', 'too large' => 999999]);

it('does not price a hidden product', function () {
    $product = Product::factory()->inactive()->create();

    $this->getJson(route('products.price', [$product->slug, 'quantite' => 1]))->assertNotFound();
});
