<?php

namespace Database\Factories\Core\Content;

use App\Models\Core\Content\FaqCategory;
use App\Models\Core\Content\FaqItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FaqItem>
 */
class FaqItemFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'faq_category_id' => FaqCategory::factory(),
            'question' => rtrim(fake()->sentence(6), '.').' ?',
            'answer' => fake()->paragraph(),
            'sort_order' => 0,
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }
}
