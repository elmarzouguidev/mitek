<?php

use App\Models\Core\Product\Brand;
use App\Models\Core\Product\Category;
use App\Models\Core\Product\Product;
use App\Support\Money;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $this->parent = Category::factory()->create(['name' => 'Consommables']);
    $this->toners = Category::factory()->childOf($this->parent)->create(['name' => 'Toners']);
    $this->ribbons = Category::factory()->childOf($this->parent)->create(['name' => 'Rubans']);
});

it('lists the products of the category and of its subcategories with TTC and HT prices', function () {
    Product::factory()->for($this->toners)->priced(100)->create(['name' => 'Toner A']);
    Product::factory()->for($this->ribbons)->priced(50)->create(['name' => 'Ruban B']);
    Product::factory()->for(Category::factory())->create(['name' => 'Elsewhere C']);

    $this->get(route('categories.show', $this->parent->slug))
        ->assertOk()
        ->assertSee('Toner A')
        ->assertSee('Ruban B')
        ->assertDontSee('Elsewhere C')
        ->assertSee(Money::fromDecimal(120)->format(), false)
        ->assertSee(Money::fromDecimal(100)->format(), false);
});

it('filters by subcategory', function () {
    Product::factory()->for($this->toners)->create(['name' => 'Toner A']);
    Product::factory()->for($this->ribbons)->create(['name' => 'Ruban B']);

    $this->get(route('categories.show', [$this->parent->slug, 'sous_categorie' => $this->ribbons->slug]))
        ->assertSee('Ruban B')
        ->assertDontSee('Toner A');
});

it('ignores a subcategory filter that is not a child of the category', function () {
    $other = Category::factory()->create();
    Product::factory()->for($this->toners)->create(['name' => 'Toner A']);

    $this->get(route('categories.show', [$this->parent->slug, 'sous_categorie' => $other->slug]))
        ->assertSee('Toner A');
});

it('filters by one or several brands', function () {
    $hp = Brand::factory()->create(['name' => 'HP']);
    $canon = Brand::factory()->create(['name' => 'Canon']);
    $dell = Brand::factory()->create(['name' => 'Dell']);
    Product::factory()->for($this->toners)->withBrand($hp)->create(['name' => 'HP Toner']);
    Product::factory()->for($this->toners)->withBrand($canon)->create(['name' => 'Canon Toner']);
    Product::factory()->for($this->toners)->withBrand($dell)->create(['name' => 'Dell Toner']);

    $this->get(route('categories.show', [$this->parent->slug, 'marques' => ['hp']]))
        ->assertSee('HP Toner')
        ->assertDontSee('Canon Toner');

    $this->get(route('categories.show', [$this->parent->slug, 'marques' => ['hp', 'canon']]))
        ->assertSee('HP Toner')
        ->assertSee('Canon Toner')
        ->assertDontSee('Dell Toner');
});

it('only offers brands that have visible products in the category', function () {
    $present = Brand::factory()->create(['name' => 'Present Brand']);
    $absent = Brand::factory()->create(['name' => 'Absent Brand']);
    $disabled = Brand::factory()->inactive()->create(['name' => 'Disabled Brand']);
    Product::factory()->for($this->toners)->withBrand($present)->create();
    Product::factory()->inactive()->for($this->toners)->withBrand($absent)->create();
    Product::factory()->for($this->toners)->withBrand($disabled)->create();

    $this->get(route('categories.show', $this->parent->slug))
        ->assertSee('value="present-brand"', false)
        ->assertDontSee('value="absent-brand"', false)
        ->assertDontSee('value="disabled-brand"', false);
});

it('filters by price range on the TTC price, bounds included', function () {
    Product::factory()->for($this->toners)->priced(50)->create(['name' => 'Cheap']);    // 60 TTC
    Product::factory()->for($this->toners)->priced(100)->create(['name' => 'Middle']);  // 120 TTC
    Product::factory()->for($this->toners)->priced(200)->create(['name' => 'Costly']);  // 240 TTC

    $this->get(route('categories.show', [$this->parent->slug, 'prix_min' => 120, 'prix_max' => 240]))
        ->assertSee('Middle')
        ->assertSee('Costly')
        ->assertDontSee('Cheap');

    $this->get(route('categories.show', [$this->parent->slug, 'prix_max' => 119]))
        ->assertSee('Cheap')
        ->assertDontSee('Middle');
});

it('uses the product own VAT rate in the price filter', function () {
    Product::factory()->for($this->toners)->priced(100)->create(['name' => 'Reduced VAT', 'vat_rate' => 0]); // 100 TTC

    $this->get(route('categories.show', [$this->parent->slug, 'prix_max' => 110]))->assertSee('Reduced VAT');
    $this->get(route('categories.show', [$this->parent->slug, 'prix_min' => 110]))->assertDontSee('Reduced VAT');
});

it('filters by availability', function () {
    Product::factory()->for($this->toners)->create(['name' => 'Available']);
    Product::factory()->for($this->toners)->outOfStock()->create(['name' => 'Sold Out']);

    $this->get(route('categories.show', [$this->parent->slug, 'en_stock' => 1]))
        ->assertSee('Available')
        ->assertDontSee('Sold Out');

    $this->get(route('categories.show', $this->parent->slug))->assertSee('Sold Out');
});

it('sorts by price', function () {
    Product::factory()->for($this->toners)->priced(300)->create(['name' => 'Third']);
    Product::factory()->for($this->toners)->priced(100)->create(['name' => 'First']);
    Product::factory()->for($this->toners)->priced(200)->create(['name' => 'Second']);

    $this->get(route('categories.show', [$this->parent->slug, 'tri' => 'prix_croissant']))
        ->assertSeeInOrder(['First', 'Second', 'Third']);
    $this->get(route('categories.show', [$this->parent->slug, 'tri' => 'prix_decroissant']))
        ->assertSeeInOrder(['Third', 'Second', 'First']);
});

it('puts featured products first by default', function () {
    Product::factory()->for($this->toners)->create(['name' => 'Plain Product']);
    Product::factory()->for($this->toners)->featured()->create(['name' => 'Star Product']);

    $this->get(route('categories.show', $this->parent->slug))->assertSeeInOrder(['Star Product', 'Plain Product']);
});

it('ignores unusable filter values instead of failing', function () {
    Product::factory()->for($this->toners)->create(['name' => 'Still Listed']);

    $this->get(route('categories.show', [$this->parent->slug, 'prix_min' => 'abc', 'prix_max' => '-5', 'tri' => 'bogus', 'en_stock' => 'maybe']))
        ->assertOk()
        ->assertSee('Still Listed');
});

it('keeps the filters in the pagination links', function () {
    Product::factory()->count(13)->for($this->toners)->create();

    $this->get(route('categories.show', [$this->parent->slug, 'tri' => 'nom', 'en_stock' => 1]))
        ->assertOk()
        ->assertSee('tri=nom', false)
        ->assertSee('en_stock=1', false)
        ->assertSee('page=2', false);
});

it('shows a message when no product matches', function () {
    $this->get(route('categories.show', [$this->parent->slug, 'prix_min' => 99999]))
        ->assertOk()
        ->assertSee('Aucun produit ne correspond à votre sélection.');
});

it('shows the stock status of each product', function () {
    Product::factory()->for($this->toners)->create(['name' => 'Plenty', 'stock_quantity' => 50]);
    Product::factory()->for($this->toners)->create(['name' => 'Few', 'stock_quantity' => 3]);
    Product::factory()->for($this->toners)->outOfStock()->create(['name' => 'None']);

    $this->get(route('categories.show', $this->parent->slug))
        ->assertSee('En stock')
        ->assertSee('Stock limité')
        ->assertSee('Rupture de stock');
});

it('does not run extra queries per product on a listing page', function () {
    $queries = function () {
        DB::flushQueryLog();
        DB::enableQueryLog();
        $this->get(route('categories.show', $this->parent->slug))->assertOk();
        $count = count(DB::getQueryLog());
        DB::disableQueryLog();

        return $count;
    };

    Product::factory()->for($this->toners)->withBrand()->withTiers([3 => 5])->create();
    $queries(); // warms the cached header menu
    $withOne = $queries();

    Product::factory()->count(11)->for($this->toners)->withBrand()->withTiers([3 => 5])->create();
    $queries(); // creating products flushed the cached menu
    $withTwelve = $queries();

    expect($withTwelve)->toBe($withOne);
});
