<?php

use App\Models\Core\Product\Category;
use App\Models\Core\Product\Product;

it('serves active products and categories', function () {
    $product = Product::factory()->create();

    $this->get(route('products.show', $product->slug))->assertOk();
    $this->get(route('categories.show', $product->category->slug))->assertOk();
});

it('returns 404 for an inactive product page', function () {
    $product = Product::factory()->inactive()->create();

    $this->get(route('products.show', $product->slug))->assertNotFound();
});

it('returns 404 for a soft deleted product page', function () {
    $product = Product::factory()->create();
    $product->delete();

    $this->get(route('products.show', $product->slug))->assertNotFound();
});

it('returns 404 for an inactive category page', function () {
    $category = Category::factory()->inactive()->create();

    $this->get(route('categories.show', $category->slug))->assertNotFound();
});

it('returns 404 for an unknown slug', function () {
    $this->get(route('categories.show', 'does-not-exist'))->assertNotFound();
    $this->get(route('products.show', 'does-not-exist'))->assertNotFound();
});

it('hides inactive products from category listings, search and suggestions', function () {
    $category = Category::factory()->create();
    Product::factory()->for($category)->create(['name' => 'Visible Scanner']);
    Product::factory()->for($category)->inactive()->create(['name' => 'Hidden Scanner']);

    $this->get(route('categories.show', $category->slug))
        ->assertSee('Visible Scanner')
        ->assertDontSee('Hidden Scanner');

    $this->get(route('search', ['q' => 'Scanner']))
        ->assertSee('Visible Scanner')
        ->assertDontSee('Hidden Scanner');

    $this->getJson(route('search.suggestions', ['q' => 'Scanner']))
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', 'Visible Scanner');
});

it('hides products whose category is inactive', function () {
    $inactive = Category::factory()->inactive()->create();
    $product = Product::factory()->for($inactive)->create(['name' => 'Orphaned Printer']);

    $this->get(route('products.show', $product->slug))->assertNotFound();
    $this->get(route('search', ['q' => 'Orphaned']))->assertDontSee('Orphaned Printer');
});

it('hides the subcategories and products of an inactive parent category', function () {
    $parent = Category::factory()->inactive()->create();
    $child = Category::factory()->childOf($parent)->create();
    $product = Product::factory()->for($child)->create(['name' => 'Nested Router']);

    $this->get(route('categories.show', $child->slug))->assertNotFound();
    $this->get(route('products.show', $product->slug))->assertNotFound();
    $this->get(route('search', ['q' => 'Nested']))->assertDontSee('Nested Router');
});

it('does not list inactive subcategories on the parent page', function () {
    $parent = Category::factory()->create();
    Category::factory()->childOf($parent)->create(['name' => 'Active Child']);
    Category::factory()->childOf($parent)->inactive()->create(['name' => 'Inactive Child']);

    $this->get(route('categories.show', $parent->slug))
        ->assertSee('Active Child')
        ->assertDontSee('Inactive Child');
});

it('keeps inactive categories out of the header menu', function () {
    Category::factory()->create(['name' => 'Menu Visible']);
    Category::factory()->inactive()->create(['name' => 'Menu Hidden']);

    $this->get(route('home'))
        ->assertSee('Menu Visible')
        ->assertDontSee('Menu Hidden');
});

it('refreshes the header menu when a category changes', function () {
    $category = Category::factory()->create(['name' => 'Cached Name']);
    $this->get(route('home'))->assertSee('Cached Name');

    $category->update(['is_active' => false]);

    $this->get(route('home'))->assertDontSee('Cached Name');
});
