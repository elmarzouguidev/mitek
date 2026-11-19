<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([RoleSeeder::class, ShippingZoneSeeder::class, ServiceSeeder::class, PageSeeder::class, FaqSeeder::class, ProductSeeder::class]);

        if (app()->environment('local')) {
            User::factory()->create([
                'name' => 'Administrateur Mitek',
                'email' => 'admin@mitek.test',
            ])->assignRole(UserRole::Admin->value);
        }
    }
}
