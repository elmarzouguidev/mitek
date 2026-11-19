<?php

namespace Database\Seeders;

use App\Models\Core\Product\Brand;
use Illuminate\Database\Seeder;

class BrandSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['HP', 'Lexmark', 'Canon', 'Brother', 'Grandstream', 'Wacom', 'Dell'] as $name) {
            Brand::firstOrCreate(['name' => $name]);
        }
    }
}
