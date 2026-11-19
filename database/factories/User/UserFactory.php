<?php

namespace Database\Factories\User;

use App\Enums\UserRole;
use App\Models\User\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    /**
     * Give the user the back-office administrator role.
     */
    public function admin(): static
    {
        return $this->afterCreating(fn (User $user) => $user->assignRole(UserRole::Admin->value));
    }

    /**
     * Give the user the customer role.
     */
    public function customer(): static
    {
        return $this->afterCreating(fn (User $user) => $user->assignRole(UserRole::Customer->value));
    }
}
