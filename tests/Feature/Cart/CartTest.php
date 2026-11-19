<?php

use App\Models\Core\Product\Product;
use App\Services\Core\Cart\CartService;
use App\Support\Money;

it('adds a product to the cart and shows it', function () {
    $product = Product::factory()->priced(100)->create(['name' => 'Toner noir']);

    $this->post(route('cart.store'), ['product' => $product->slug, 'quantite' => 2])
        ->assertRedirect(route('cart.show'))
        ->assertSessionHas('success');

    $this->get(route('cart.show'))
        ->assertOk()
        ->assertSee('Toner noir')
        ->assertSee(Money::fromDecimal(240)->format());
});

it('adds to the quantity already in the cart', function () {
    $product = Product::factory()->create(['stock_quantity' => 50]);

    $this->post(route('cart.store'), ['product' => $product->slug, 'quantite' => 2]);
    $this->post(route('cart.store'), ['product' => $product->slug, 'quantite' => 3]);

    expect(app(CartService::class)->summary()->lines->first()->quantity)->toBe(5);
});

it('prices each line with the quantity tier through the PriceCalculator', function () {
    $product = Product::factory()->priced(100)->withTiers([5 => 90])->create(['stock_quantity' => 50]);

    $this->withSession(cartSession([[$product, 4]]))->get(route('cart.show'))->assertDontSee('Tarif dégressif');
    $this->withSession(cartSession([[$product, 5]]))->get(route('cart.show'))
        ->assertSee('Tarif dégressif')
        ->assertSee(Money::fromDecimal(540)->format());
});

it('shows HT, VAT and TTC totals of the cart', function () {
    $product = Product::factory()->priced(100)->create(['stock_quantity' => 50]);

    $this->withSession(cartSession([[$product, 3]]))->get(route('cart.show'))
        ->assertSee(Money::fromDecimal(300)->format())
        ->assertSee(Money::fromDecimal(60)->format())
        ->assertSee(Money::fromDecimal(360)->format());
});

it('updates a quantity and removes a line when set to zero', function () {
    $product = Product::factory()->create(['stock_quantity' => 50]);
    $this->withSession(cartSession([[$product, 2]]));

    $this->patch(route('cart.update', $product->slug), ['quantite' => 7])->assertRedirect(route('cart.show'));
    expect(app(CartService::class)->count())->toBe(7);

    $this->patch(route('cart.update', $product->slug), ['quantite' => 0]);
    expect(app(CartService::class)->count())->toBe(0);
});

it('removes a line', function () {
    $product = Product::factory()->create();
    $this->withSession(cartSession([[$product, 2]]));

    $this->delete(route('cart.destroy', $product->slug))->assertSessionHas('success');

    expect(app(CartService::class)->count())->toBe(0);
});

it('refuses more than the available stock', function () {
    $product = Product::factory()->create(['stock_quantity' => 3, 'name' => 'Rare item']);

    $this->post(route('cart.store'), ['product' => $product->slug, 'quantite' => 4])
        ->assertSessionHasErrors('quantite');

    expect(app(CartService::class)->count())->toBe(0);
});

it('counts what is already in the cart against the stock', function () {
    $product = Product::factory()->create(['stock_quantity' => 5]);
    $this->withSession(cartSession([[$product, 4]]));

    $this->post(route('cart.store'), ['product' => $product->slug, 'quantite' => 2])->assertSessionHasErrors('quantite');

    expect(app(CartService::class)->count())->toBe(4);
});

it('refuses out of stock products', function () {
    $product = Product::factory()->outOfStock()->create();

    $this->post(route('cart.store'), ['product' => $product->slug, 'quantite' => 1])->assertSessionHasErrors('quantite');
});

it('cannot add hidden or unknown products', function () {
    $inactive = Product::factory()->inactive()->create();

    $this->post(route('cart.store'), ['product' => $inactive->slug, 'quantite' => 1])->assertNotFound();
    $this->post(route('cart.store'), ['product' => 'does-not-exist', 'quantite' => 1])->assertNotFound();
});

it('validates the quantity', function (mixed $quantity) {
    $product = Product::factory()->create();

    $this->post(route('cart.store'), ['product' => $product->slug, 'quantite' => $quantity])->assertSessionHasErrors('quantite');
})->with(['zero' => 0, 'negative' => -2, 'text' => 'abc', 'huge' => 999999]);

it('drops lines whose product was deactivated and says so', function () {
    $kept = Product::factory()->create(['name' => 'Still here', 'stock_quantity' => 10]);
    $gone = Product::factory()->create(['name' => 'Withdrawn item']);
    $this->withSession(cartSession([[$kept, 1], [$gone, 1]]));
    $gone->update(['is_active' => false]);

    $this->get(route('cart.show'))
        ->assertSee('Still here')
        ->assertSee('« Withdrawn item » n\'est plus disponible et a été retiré du panier.');

    expect(app(CartService::class)->count())->toBe(1);
});

it('reduces quantities that exceed the stock since they were added', function () {
    $product = Product::factory()->create(['name' => 'Shrinking stock', 'stock_quantity' => 10]);
    $this->withSession(cartSession([[$product, 8]]));
    $product->update(['stock_quantity' => 3]);

    $this->get(route('cart.show'))->assertSee('a été réduite à 3', false);

    expect(app(CartService::class)->summary()->lines->first()->quantity)->toBe(3);
});

it('drops lines that sold out', function () {
    $product = Product::factory()->create();
    $this->withSession(cartSession([[$product, 2]]));
    $product->update(['stock_quantity' => 0]);

    expect(app(CartService::class)->summary()->isEmpty())->toBeTrue();
});

it('always reprices from the catalogue', function () {
    $product = Product::factory()->priced(100)->create(['stock_quantity' => 10]);
    $this->withSession(cartSession([[$product, 1]]));
    $product->update(['price_ht' => Money::fromDecimal(150)]);

    $this->get(route('cart.show'))->assertSee(Money::fromDecimal(180)->format());
});

it('shows the cart size in the header', function () {
    $product = Product::factory()->create(['stock_quantity' => 10]);

    $this->withSession(cartSession([[$product, 3]]))->get(route('home'))->assertSee('Panier')->assertSee('>3<', false);
});

it('shows an empty cart message', function () {
    $this->get(route('cart.show'))->assertOk()->assertSee('Votre panier est vide.');
});

it('does not run extra queries per cart line', function () {
    $count = function (array $lines) {
        DB::flushQueryLog();
        DB::enableQueryLog();
        $this->withSession(cartSession($lines))->get(route('cart.show'))->assertOk();
        $total = count(DB::getQueryLog());
        DB::disableQueryLog();

        return $total;
    };

    $products = Product::factory()->count(6)->withBrand()->withTiers([3 => 5])->create(['stock_quantity' => 20]);
    $count([[$products[0], 1]]); // warms the cached header menu
    $one = $count([[$products[0], 1]]);
    $six = $count($products->map(fn ($product) => [$product, 2])->all());

    expect($six)->toBe($one);
});
