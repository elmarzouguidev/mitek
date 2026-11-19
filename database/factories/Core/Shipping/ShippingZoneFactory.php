<?php

namespace Database\Factories\Core\Shipping;

use App\Models\Core\Shipping\ShippingZone;
use App\Support\Money;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ShippingZone>
 */
class ShippingZoneFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->city(),
            'is_default' => false,
            'is_active' => true,
            'transit_min_days' => 1,
            'transit_max_days' => 3,
        ];
    }

    public function default(): static
    {
        return $this->state(['is_default' => true]);
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }

    /**
     * @param  array<int, string>  $cities
     */
    public function withCities(array $cities): static
    {
        return $this->afterCreating(function (ShippingZone $zone) use ($cities): void {
            foreach ($cities as $city) {
                $zone->cities()->create(['city' => $city]);
            }
        });
    }

    /**
     * Weight brackets as [min grams, max grams or null, price HT in dirhams].
     *
     * @param  array<int, array{0: int, 1: int|null, 2: string|int|float}>  $brackets
     */
    public function withRates(array $brackets): static
    {
        return $this->afterCreating(function (ShippingZone $zone) use ($brackets): void {
            foreach ($brackets as [$min, $max, $price]) {
                $zone->rates()->create(['min_weight_g' => $min, 'max_weight_g' => $max, 'price_ht' => Money::fromDecimal($price)]);
            }
        });
    }

    /**
     * A zone with one open bracket at a flat price: the quickest way to make shipping available in a test.
     */
    public function flatRate(string|int|float $price = 30): static
    {
        return $this->withRates([[0, null, $price]]);
    }
}
