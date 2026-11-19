<?php

namespace Database\Factories\Core\Service;

use App\Models\Core\Service\Service;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Service>
 */
class ServiceFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => fake()->unique()->words(3, true),
            'icon' => 'wrench',
            'summary' => fake()->sentence(),
            'bullets' => [fake()->sentence(4), fake()->sentence(4)],
            'content' => null,
            'sort_order' => 0,
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }
}
