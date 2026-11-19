<?php

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Models\Core\Order\Order;
use App\Models\Core\Product\Product;
use App\Models\User\User;
use App\Support\Money;
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

it('shows the order figures: payments to validate, orders to process and recent revenue', function () {
    Order::factory()->status(OrderStatus::PaymentSubmitted)->count(2)->create();
    Order::factory()->status(OrderStatus::Paid)->totalling(100)->create();
    Order::factory()->status(OrderStatus::Processing)->totalling(200)->create();
    Order::factory()->status(OrderStatus::Delivered)->totalling(300)->create();
    Order::factory()->status(OrderStatus::Delivered)->totalling(900)->create(['created_at' => now()->subDays(45)]);
    Order::factory()->status(OrderStatus::Cancelled)->totalling(700)->create();
    Order::factory()->create(); // pending bank transfer: nothing to do yet
    Order::factory()->method(PaymentMethod::CashOnDelivery)->create(); // COD awaiting confirmation

    $this->actingAs(User::factory()->admin()->create())->get(route('admin.dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('stats.awaiting_validation', 2)
            ->where('stats.to_process', 3)
            ->where('stats.revenue_30_days', Money::fromDecimal(600)->format()));
});
