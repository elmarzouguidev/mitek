<?php

use App\Models\Core\Product\Brand;
use App\Models\Core\Product\Product;
use App\Models\User\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    Storage::fake('public');
    $this->admin = User::factory()->admin()->create();
});

it('keeps brand management away from guests and customers', function () {
    $brand = Brand::factory()->create();
    $customer = User::factory()->customer()->create();

    foreach ([['get', route('admin.brands.index')], ['post', route('admin.brands.store')], ['put', route('admin.brands.update', $brand)], ['delete', route('admin.brands.destroy', $brand)]] as [$method, $url]) {
        $this->$method($url)->assertRedirect(route('login'));
        $this->actingAs($customer)->$method($url)->assertForbidden();
        auth()->logout();
    }
});

it('creates a brand with a logo and a slug', function () {
    $this->actingAs($this->admin)->post(route('admin.brands.store'), [
        'name' => 'Grandstream', 'is_active' => true, 'logo' => UploadedFile::fake()->image('logo.png', 300, 120),
    ])->assertRedirect(route('admin.brands.index'));

    $brand = Brand::firstOrFail();

    expect($brand->slug)->toBe('grandstream')
        ->and($brand->getMedia('logo'))->toHaveCount(1);
});

it('requires a unique brand name', function () {
    Brand::factory()->create(['name' => 'HP']);

    $this->actingAs($this->admin)->post(route('admin.brands.store'), ['name' => 'HP', 'is_active' => true])
        ->assertSessionHasErrors('name');
});

it('updates a brand keeping its own name valid', function () {
    $brand = Brand::factory()->create(['name' => 'Dell']);

    $this->actingAs($this->admin)->put(route('admin.brands.update', $brand), ['name' => 'Dell', 'is_active' => false])
        ->assertSessionHasNoErrors();

    expect($brand->fresh()->is_active)->toBeFalse();
});

it('removes the logo on request', function () {
    $brand = Brand::factory()->create();
    $brand->addMedia(UploadedFile::fake()->image('logo.png'))->toMediaCollection('logo');

    $this->actingAs($this->admin)->put(route('admin.brands.update', $brand), ['name' => $brand->name, 'is_active' => true, 'remove_logo' => true]);

    expect($brand->fresh()->getMedia('logo'))->toHaveCount(0);
});

it('lists, searches and counts brand products', function () {
    $hp = Brand::factory()->create(['name' => 'HP']);
    Brand::factory()->create(['name' => 'Canon']);
    Product::factory()->count(2)->withBrand($hp)->create();

    $this->actingAs($this->admin)->get(route('admin.brands.index', ['q' => 'hp']))
        ->assertInertia(fn (Assert $page) => $page
            ->component('Brands/Index')
            ->has('brands.data', 1)
            ->where('brands.data.0.name', 'HP')
            ->where('brands.data.0.products_count', 2));
});

it('deletes a brand and keeps its products without a brand', function () {
    $brand = Brand::factory()->create();
    $product = Product::factory()->withBrand($brand)->create();

    $this->actingAs($this->admin)->delete(route('admin.brands.destroy', $brand))->assertRedirect(route('admin.brands.index'));

    $this->assertModelMissing($brand);
    expect($product->fresh()->brand_id)->toBeNull();
});
