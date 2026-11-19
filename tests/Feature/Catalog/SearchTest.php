<?php

use App\Models\Core\Product\Brand;
use App\Models\Core\Product\CompatibleModel;
use App\Models\Core\Product\Product;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $this->hp = Brand::factory()->create(['name' => 'HP']);
    $this->toner = Product::factory()->withBrand($this->hp)->create(['name' => 'Toner noir 312A', 'reference' => 'CF380A']);
    $this->toner->compatibleModels()->attach(CompatibleModel::factory()->create(['name' => 'Color LaserJet Pro M476dn']));
    $this->other = Product::factory()->create(['name' => 'Cahier spirale', 'reference' => 'CAH-001']);
});

it('finds products by name', function () {
    $this->get(route('search', ['q' => 'toner']))
        ->assertOk()
        ->assertSee('Toner noir 312A')
        ->assertDontSee('Cahier spirale');
});

it('finds products by reference', function () {
    $this->get(route('search', ['q' => 'cf380a']))->assertSee('Toner noir 312A');
});

it('finds products by brand name', function () {
    $this->get(route('search', ['q' => 'HP']))
        ->assertSee('Toner noir 312A')
        ->assertDontSee('Cahier spirale');
});

it('finds products by compatible printer model', function () {
    $this->get(route('search', ['q' => 'M476dn']))
        ->assertSee('Toner noir 312A')
        ->assertDontSee('Cahier spirale');
});

it('requires every word to match something', function () {
    $this->get(route('search', ['q' => 'HP 312A']))->assertSee('Toner noir 312A');
    $this->get(route('search', ['q' => 'HP cahier']))->assertDontSee('Toner noir 312A')->assertDontSee('Cahier spirale');
});

it('treats LIKE wildcards in the query as plain characters', function () {
    $this->get(route('search', ['q' => '%%']))
        ->assertDontSee('Toner noir 312A')
        ->assertDontSee('Cahier spirale');

    $this->getJson(route('search.suggestions', ['q' => '__']))->assertJsonCount(0, 'data');
});

it('asks for a longer query when it is too short', function () {
    $this->get(route('search', ['q' => 'a']))->assertOk()->assertSee('Saisissez au moins 2 caractères');
    $this->get(route('search'))->assertOk()->assertSee('Saisissez au moins 2 caractères');
});

it('shows an empty state when nothing matches', function () {
    $this->get(route('search', ['q' => 'zzzzzz']))->assertOk()->assertSee('Aucun produit ne correspond');
});

it('escapes the query on the results page', function () {
    $this->get(route('search', ['q' => '<script>alert(1)</script>']))
        ->assertDontSee('<script>alert(1)</script>', false)
        ->assertSee('&lt;script&gt;', false);
});

it('does not fail on an oversized query', function () {
    $this->get(route('search', ['q' => str_repeat('a', 5000)]))->assertOk();
});

it('suggests at most 8 products with the fields the autocomplete needs', function () {
    Product::factory()->count(12)->sequence(fn ($sequence) => ['name' => 'Ramette papier '.$sequence->index])->create();

    $response = $this->getJson(route('search.suggestions', ['q' => 'ramette']))
        ->assertOk()
        ->assertJsonCount(8, 'data')
        ->assertJsonStructure(['data' => [['name', 'reference', 'brand', 'price', 'image', 'url']]]);

    expect($response->json('data.0.url'))->toStartWith(url('/produit/'));
});

it('returns no suggestions for a query shorter than two characters', function () {
    $this->getJson(route('search.suggestions', ['q' => 't']))->assertOk()->assertExactJson(['data' => []]);
});

it('ranks an exact reference match first', function () {
    Product::factory()->create(['name' => 'Accessoire CF380A compatible', 'reference' => 'ACC-1']);

    $this->getJson(route('search.suggestions', ['q' => 'CF380A']))
        ->assertJsonPath('data.0.reference', 'CF380A');
});

it('throttles the suggestions endpoint', function () {
    foreach (range(1, 60) as $attempt) {
        $this->getJson(route('search.suggestions', ['q' => 'toner']))->assertOk();
    }

    $this->getJson(route('search.suggestions', ['q' => 'toner']))->assertStatus(429);
});

it('does not run extra queries per result', function () {
    $count = function (string $url) {
        DB::flushQueryLog();
        DB::enableQueryLog();
        $this->get($url)->assertOk();
        $total = count(DB::getQueryLog());
        DB::disableQueryLog();

        return $total;
    };

    Product::factory()->withBrand()->withTiers([3 => 5])->create(['name' => 'Ramette 0']);
    $count(route('search', ['q' => 'ramette'])); // warms the cached header menu
    $one = $count(route('search', ['q' => 'ramette']));

    Product::factory()->count(9)->withBrand()->withTiers([3 => 5])->create(['name' => 'Ramette x']);
    $count(route('search', ['q' => 'ramette'])); // the factories created categories, which flushed the menu cache
    $ten = $count(route('search', ['q' => 'ramette']));

    expect($ten)->toBe($one);
});
