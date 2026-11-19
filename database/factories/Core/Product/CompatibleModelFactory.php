<?php

namespace Database\Factories\Core\Product;

use App\Models\Core\Product\CompatibleModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CompatibleModel>
 */
class CompatibleModelFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => strtoupper(fake()->unique()->bothify('LaserJet ??-####')),
        ];
    }
}
