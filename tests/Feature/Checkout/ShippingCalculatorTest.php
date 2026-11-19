<?php

use App\Enums\CarrierMode;
use App\Exceptions\ShippingUnavailable;
use App\Models\Core\Shipping\ShippingZone;
use App\Services\Core\Shipping\ShippingCalculator;
use App\Settings\ShippingSettings;

function shipping(string $city, int $weight, CarrierMode $mode = CarrierMode::Mitek): int
{
    return app(ShippingCalculator::class)->quote($city, $weight, $mode)->priceHt->cents;
}

beforeEach(function () {
    $this->casablanca = ShippingZone::factory()->withCities(['Casablanca'])->withRates([[0, 5000, 25], [5001, null, 60]])->create(['name' => 'Casa']);
    $this->fallback = ShippingZone::factory()->default()->withRates([[0, 5000, 45], [5001, 15000, 80]])->create(['name' => 'Rest']);
});

it('picks the zone of the city', function () {
    $quote = app(ShippingCalculator::class)->quote('Casablanca', 1000, CarrierMode::Mitek);

    expect($quote->zone->id)->toBe($this->casablanca->id)
        ->and($quote->priceHt->cents)->toBe(2500);
});

it('matches cities regardless of case, accents and spacing', function () {
    ShippingZone::factory()->withCities(['Fès'])->flatRate(55)->create();

    expect(shipping('fes', 100))->toBe(5500)
        ->and(shipping('  FÈS ', 100))->toBe(5500)
        ->and(shipping('casablanca', 100))->toBe(2500);
});

it('falls back to the default zone for unknown cities', function () {
    expect(shipping('Ifrane', 1000))->toBe(4500);
});

it('selects the weight bracket, bounds included', function (int $grams, int $expectedCents) {
    expect(shipping('Casablanca', $grams))->toBe($expectedCents);
})->with([
    'empty parcel' => [0, 2500],
    'upper bound of first bracket' => [5000, 2500],
    'lower bound of second bracket' => [5001, 6000],
    'open ended bracket' => [900000, 6000],
]);

it('fails when the weight exceeds the last bracket', function () {
    shipping('Ifrane', 15001);
})->throws(ShippingUnavailable::class);

it('fails when there is no default zone and the city is unknown', function () {
    $this->fallback->delete();

    shipping('Ifrane', 100);
})->throws(ShippingUnavailable::class);

it('ignores inactive zones', function () {
    $this->casablanca->update(['is_active' => false]);

    expect(shipping('Casablanca', 1000))->toBe(4500);
});

it('charges the own carrier fee from the settings and needs no zone', function () {
    $settings = app(ShippingSettings::class);
    $settings->own_carrier_fee_cents = 5000;
    $settings->save();
    ShippingZone::query()->delete();

    $quote = app(ShippingCalculator::class)->quote('Anywhere', 99999, CarrierMode::Own);

    expect($quote->priceHt->cents)->toBe(5000)
        ->and($quote->zone)->toBeNull();
});
