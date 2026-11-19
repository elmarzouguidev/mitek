<?php

use App\Models\Core\Product\Brand;
use App\Models\Core\Product\Category;
use App\Models\Core\Product\CompatibleModel;
use App\Models\Core\Product\Product;
use App\Models\User\User;
use Inertia\Testing\AssertableInertia as Assert;

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function productPayload(array $overrides = []): array
{
    return array_merge([
        'category_id' => Category::factory()->create()->id,
        'brand_id' => null,
        'reference' => 'CF380A',
        'name' => 'Toner HP 312A noir',
        'excerpt' => 'Toner original.',
        'description' => 'Description détaillée.',
        'price_ht' => '790.50',
        'vat_rate' => null,
        'stock_quantity' => 40,
        'low_stock_threshold' => 5,
        'weight_grams' => 780,
        'is_active' => true,
        'is_featured' => false,
        'meta_title' => null,
        'meta_description' => null,
        'tiers' => [],
        'specifications' => [],
        'compatible_models' => [],
    ], $overrides);
}

beforeEach(function () {
    $this->admin = User::factory()->admin()->create();
});

it('keeps the product screens away from guests and customers', function () {
    $product = Product::factory()->create();
    $customer = User::factory()->customer()->create();

    $routes = [
        ['get', route('admin.products.index')],
        ['get', route('admin.products.create')],
        ['post', route('admin.products.store')],
        ['get', route('admin.products.edit', $product)],
        ['put', route('admin.products.update', $product)],
        ['delete', route('admin.products.destroy', $product)],
        ['post', route('admin.products.bulk')],
    ];

    foreach ($routes as [$method, $url]) {
        $this->$method($url)->assertRedirect(route('login'));
        $this->actingAs($customer)->$method($url)->assertForbidden();
        auth()->logout();
    }
});

it('denies catalog policies to customers and allows administrators', function () {
    $customer = User::factory()->customer()->create();

    foreach ([Product::class, Category::class, Brand::class] as $model) {
        expect($customer->can('viewAny', $model))->toBeFalse()
            ->and($customer->can('create', $model))->toBeFalse()
            ->and($this->admin->can('viewAny', $model))->toBeTrue()
            ->and($this->admin->can('create', $model))->toBeTrue();
    }

    $product = Product::factory()->create();

    expect($customer->can('update', $product))->toBeFalse()
        ->and($customer->can('delete', $product))->toBeFalse()
        ->and($this->admin->can('update', $product))->toBeTrue()
        ->and($this->admin->can('delete', $product))->toBeTrue();
});

it('creates a product with its tiers, specifications and compatible models', function () {
    $brand = Brand::factory()->create();

    $response = $this->actingAs($this->admin)->post(route('admin.products.store'), productPayload([
        'brand_id' => $brand->id,
        'tiers' => [
            ['min_quantity' => 3, 'price_ht' => '750'],
            ['min_quantity' => 10, 'price_ht' => '700.25'],
        ],
        'specifications' => [
            ['name' => 'Couleur', 'value' => 'Noir'],
            ['name' => 'Type', 'value' => "Toner d'origine"],
        ],
        'compatible_models' => ['HP LaserJet M476dn', 'hp laserjet m476dn ', 'HP LaserJet M476nw'],
    ]));

    $product = Product::where('reference', 'CF380A')->firstOrFail();

    $response->assertRedirect(route('admin.products.edit', $product));
    expect($product->price_ht->cents)->toBe(79050)
        ->and($product->slug)->toBe('toner-hp-312a-noir')
        ->and($product->brand_id)->toBe($brand->id)
        ->and($product->priceTiers->map(fn ($tier) => [$tier->min_quantity, $tier->price_ht->cents])->all())->toBe([[3, 75000], [10, 70025]])
        ->and($product->specifications->pluck('value', 'name')->all())->toBe(['Couleur' => 'Noir', 'Type' => "Toner d'origine"])
        ->and($product->compatibleModels->pluck('name')->all())->toBe(['HP LaserJet M476dn', 'HP LaserJet M476nw']);
});

it('generates a reference when none is given', function () {
    $this->actingAs($this->admin)->post(route('admin.products.store'), productPayload(['reference' => null]))
        ->assertSessionHasNoErrors();

    expect(Product::firstOrFail()->reference)->toStartWith('MP-');
});

it('reuses existing compatible models instead of duplicating them', function () {
    CompatibleModel::factory()->create(['name' => 'HP LaserJet M476dn']);

    $this->actingAs($this->admin)->post(route('admin.products.store'), productPayload(['compatible_models' => ['HP LaserJet M476dn']]));

    expect(CompatibleModel::count())->toBe(1);
});

it('validates product input', function (array $overrides, string|array $errors) {
    $this->actingAs($this->admin)
        ->post(route('admin.products.store'), productPayload($overrides))
        ->assertSessionHasErrors($errors);

    expect(Product::count())->toBe(0);
})->with([
    'name required' => [['name' => ''], 'name'],
    'category required' => [['category_id' => null], 'category_id'],
    'unknown category' => [['category_id' => 9999], 'category_id'],
    'unknown brand' => [['brand_id' => 9999], 'brand_id'],
    'negative price' => [['price_ht' => '-1'], 'price_ht'],
    'text price' => [['price_ht' => 'abc'], 'price_ht'],
    'negative stock' => [['stock_quantity' => -1], 'stock_quantity'],
    'VAT above 100' => [['vat_rate' => 101], 'vat_rate'],
    'tier below minimum quantity of 2' => [['tiers' => [['min_quantity' => 1, 'price_ht' => '10']]], 'tiers.0.min_quantity'],
    'tier price above the base price' => [['price_ht' => '100', 'tiers' => [['min_quantity' => 5, 'price_ht' => '120']]], 'tiers.0.price_ht'],
    'duplicate tier quantities' => [['tiers' => [['min_quantity' => 5, 'price_ht' => '10'], ['min_quantity' => 5, 'price_ht' => '9']]], 'tiers.0.min_quantity'],
    'specification without value' => [['specifications' => [['name' => 'Couleur', 'value' => '']]], 'specifications.0.value'],
    'meta description too long' => [['meta_description' => str_repeat('a', 321)], 'meta_description'],
]);

it('requires a unique reference, ignoring the product being edited', function () {
    Product::factory()->create(['reference' => 'TAKEN']);
    $mine = Product::factory()->create(['reference' => 'MINE']);

    $this->actingAs($this->admin)->post(route('admin.products.store'), productPayload(['reference' => 'TAKEN']))
        ->assertSessionHasErrors('reference');

    $this->actingAs($this->admin)->put(route('admin.products.update', $mine), productPayload(['reference' => 'MINE']))
        ->assertSessionHasNoErrors();
});

it('updates a product and replaces its tiers and specifications', function () {
    $product = Product::factory()->priced(100)->withTiers([3 => 90, 10 => 80])->create(['name' => 'Old name']);
    $product->specifications()->create(['name' => 'Old', 'value' => 'Old', 'position' => 0]);

    $this->actingAs($this->admin)->put(route('admin.products.update', $product), productPayload([
        'category_id' => $product->category_id,
        'reference' => $product->reference,
        'name' => 'New name',
        'price_ht' => '120',
        'tiers' => [['min_quantity' => 5, 'price_ht' => '110']],
        'specifications' => [['name' => 'Poids', 'value' => '1 kg']],
    ]))->assertSessionHasNoErrors()->assertSessionHas('success');

    $product->refresh()->load(['priceTiers', 'specifications']);

    expect($product->name)->toBe('New name')
        ->and($product->slug)->not->toContain('new-name')
        ->and($product->price_ht->cents)->toBe(12000)
        ->and($product->priceTiers->pluck('min_quantity')->all())->toBe([5])
        ->and($product->specifications->pluck('name')->all())->toBe(['Poids']);
});

it('detaches compatible models that are no longer listed', function () {
    $product = Product::factory()->create();
    $product->compatibleModels()->attach(CompatibleModel::factory()->count(2)->create());

    $this->actingAs($this->admin)->put(route('admin.products.update', $product), productPayload([
        'category_id' => $product->category_id,
        'reference' => $product->reference,
        'compatible_models' => [],
    ]));

    expect($product->compatibleModels()->count())->toBe(0);
});

it('soft deletes a product', function () {
    $product = Product::factory()->create();

    $this->actingAs($this->admin)->delete(route('admin.products.destroy', $product))
        ->assertRedirect(route('admin.products.index'));

    $this->assertSoftDeleted($product);
});

it('exposes the product to the edit form with prices in dirhams', function () {
    $product = Product::factory()->priced('790.50')->withTiers([3 => '750'])->create();
    $product->compatibleModels()->attach(CompatibleModel::factory()->create(['name' => 'M476']));

    $this->actingAs($this->admin)->get(route('admin.products.edit', $product))
        ->assertInertia(fn (Assert $page) => $page
            ->component('Products/Form')
            ->where('product.id', $product->id)
            ->where('product.price_ht', 790.5)
            ->where('product.tiers.0.min_quantity', 3)
            ->where('product.tiers.0.price_ht', 750)
            ->where('product.compatible_models.0', 'M476')
            ->where('defaultVatRate', 20));
});

it('lists products with search, sorting and pagination', function () {
    Product::factory()->count(16)->create();
    Product::factory()->create(['name' => 'Needle Stapler', 'reference' => 'NDL-1']);

    $this->actingAs($this->admin)->get(route('admin.products.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('Products/Index')
            ->has('products.data', 15)
            ->where('products.meta.total', 17)
            ->where('products.meta.last_page', 2));

    $this->actingAs($this->admin)->get(route('admin.products.index', ['q' => 'needle']))
        ->assertInertia(fn (Assert $page) => $page->has('products.data', 1)->where('products.data.0.reference', 'NDL-1'));

    $this->actingAs($this->admin)->get(route('admin.products.index', ['q' => 'NDL-1']))
        ->assertInertia(fn (Assert $page) => $page->has('products.data', 1));

    $this->actingAs($this->admin)->get(route('admin.products.index', ['sort' => 'name', 'direction' => 'asc']))
        ->assertInertia(fn (Assert $page) => $page->where('products.data.0.name', Product::orderBy('name')->value('name')));
});

it('filters the product list by category, brand, status and stock level', function () {
    $category = Category::factory()->create();
    $brand = Brand::factory()->create();
    $target = Product::factory()->for($category)->withBrand($brand)->create(['stock_quantity' => 2, 'low_stock_threshold' => 5]);
    Product::factory()->inactive()->create();
    Product::factory()->outOfStock()->create();
    Product::factory()->create();

    $ids = fn (array $query) => collect($this->actingAs($this->admin)->get(route('admin.products.index', $query))
        ->viewData('page')['props']['products']['data'])->pluck('id')->all();

    expect($ids(['category_id' => $category->id]))->toBe([$target->id])
        ->and($ids(['brand_id' => $brand->id]))->toBe([$target->id])
        ->and($ids(['status' => 'inactive']))->toHaveCount(1)
        ->and($ids(['status' => 'active']))->toHaveCount(3)
        ->and($ids(['stock' => 'low']))->toHaveCount(2)
        ->and($ids(['stock' => 'out']))->toHaveCount(1);
});

it('includes soft deleted products nowhere in the list', function () {
    Product::factory()->create()->delete();

    $this->actingAs($this->admin)->get(route('admin.products.index'))
        ->assertInertia(fn (Assert $page) => $page->has('products.data', 0));
});

it('rejects unknown sort columns in the product list', function () {
    $this->actingAs($this->admin)->get(route('admin.products.index', ['sort' => 'password; drop table']))
        ->assertSessionHasErrors('sort');
});

it('activates and deactivates products in bulk', function () {
    $products = Product::factory()->count(3)->inactive()->create();
    $untouched = Product::factory()->inactive()->create();

    $this->actingAs($this->admin)->post(route('admin.products.bulk'), ['action' => 'activate', 'ids' => $products->pluck('id')->all()])
        ->assertSessionHas('success');

    expect(Product::whereIn('id', $products->pluck('id'))->where('is_active', true)->count())->toBe(3)
        ->and($untouched->fresh()->is_active)->toBeFalse();

    $this->actingAs($this->admin)->post(route('admin.products.bulk'), ['action' => 'deactivate', 'ids' => [$products[0]->id]]);

    expect($products[0]->fresh()->is_active)->toBeFalse();
});

it('validates bulk actions', function () {
    $this->actingAs($this->admin)->post(route('admin.products.bulk'), ['action' => 'explode', 'ids' => [1]])->assertSessionHasErrors('action');
    $this->actingAs($this->admin)->post(route('admin.products.bulk'), ['action' => 'activate', 'ids' => []])->assertSessionHasErrors('ids');
    $this->actingAs($this->admin)->post(route('admin.products.bulk'), ['action' => 'activate', 'ids' => [9999]])->assertSessionHasErrors('ids.0');
});
