<?php

namespace Database\Factories\Core\Content;

use App\Enums\CallbackSlot;
use App\Enums\CallbackStatus;
use App\Enums\CallbackSubject;
use App\Models\Core\Content\CallbackRequest;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CallbackRequest>
 */
class CallbackRequestFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'phone' => '06'.fake()->numerify('########'),
            'subject' => fake()->randomElement(CallbackSubject::cases()),
            'time_slot' => fake()->randomElement(CallbackSlot::cases()),
            'note' => null,
            'status' => CallbackStatus::New,
        ];
    }

    public function called(): static
    {
        return $this->state(['status' => CallbackStatus::Called, 'contacted_at' => now()]);
    }
}
