<?php

use App\Models\Core\Product\Product;
use App\Models\User\User;
use Inertia\Testing\AssertableInertia as Assert;

it('counts active products at or below their threshold and lists the emptiest first', function () {
    Product::factory()->create(['name' => 'Healthy', 'stock_quantity' => 50, 'low_stock_threshold' => 5]);
    Product::factory()->create(['name' => 'At threshold', 'stock_quantity' => 5, 'low_stock_threshold' => 5]);
    Product::factory()->create(['name' => 'Almost out', 'stock_quantity' => 1, 'low_stock_threshold' => 5]);
    Product::factory()->outOfStock()->create(['name' => 'Sold out']);
    Product::factory()->inactive()->outOfStock()->create(['name' => 'Inactive sold out']);

    $response = $this->actingAs(User::factory()->admin()->create())->get(route('admin.dashboard'))
        ->assertInertia(fn (Assert $page) => $page->where('stats.low_stock', 3));

    expect(collect($response->viewData('page')['props']['lowStockProducts'])->pluck('name')->all())
        ->toBe(['Sold out', 'Almost out', 'At threshold']);
});

it('shows at most five products in the low stock card', function () {
    Product::factory()->count(8)->outOfStock()->create();

    $this->actingAs(User::factory()->admin()->create())->get(route('admin.dashboard'))
        ->assertInertia(fn (Assert $page) => $page->where('stats.low_stock', 8)->has('lowStockProducts', 5));
});
