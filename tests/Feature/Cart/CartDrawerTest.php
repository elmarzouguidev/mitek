<?php

use App\Models\Core\Product\Category;
use App\Models\Core\Product\Product;
use App\Models\User\User;
use App\Services\Core\Cart\CartService;
use App\Settings\PaymentSettings;
use App\Support\Money;
use Illuminate\Support\Facades\Route;

it('serves an empty cart summary', function () {
    $this->getJson(route('cart.summary'))
        ->assertOk()
        ->assertJsonPath('count', 0)
        ->assertJsonPath('lines', [])
        ->assertJsonPath('cod', null)
        ->assertHeader('Cache-Control');
});

it('returns the priced lines with the shape the drawer needs', function () {
    $product = Product::factory()->priced(100)->create(['name' => 'Toner noir', 'reference' => 'TN-1', 'stock_quantity' => 50]);

    $this->withSession(cartSession([[$product, 2]]))->getJson(route('cart.summary'))
        ->assertOk()
        ->assertJsonPath('count', 2)
        ->assertJsonStructure([
            'count', 'subtotal_ht', 'vat', 'subtotal_ttc', 'cod' => ['eligible', 'limit', 'message'], 'adjustments', 'added',
            'urls' => ['cart', 'checkout', 'quote'],
            'lines' => [['slug', 'url', 'name', 'reference', 'image', 'quantity', 'max', 'unit_ttc', 'line_ttc', 'tier_applied', 'tier_label', 'update_url', 'remove_url']],
        ])
        ->assertJsonPath('lines.0.name', 'Toner noir')
        ->assertJsonPath('lines.0.reference', 'TN-1')
        ->assertJsonPath('lines.0.unit_ttc', Money::fromDecimal(120)->format())
        ->assertJsonPath('lines.0.line_ttc', Money::fromDecimal(240)->format())
        ->assertJsonPath('subtotal_ht', Money::fromDecimal(200)->format())
        ->assertJsonPath('subtotal_ttc', Money::fromDecimal(240)->format());
});

it('recomputes the tier price on the server when the quantity changes', function () {
    $product = Product::factory()->priced(100)->withTiers([5 => 90])->create(['stock_quantity' => 50]);

    $this->withSession(cartSession([[$product, 4]]))->getJson(route('cart.summary'))
        ->assertJsonPath('lines.0.tier_applied', false)
        ->assertJsonPath('lines.0.unit_ttc', Money::fromDecimal(120)->format());

    $this->patchJson(route('cart.update', $product->slug), ['quantite' => 5])
        ->assertOk()
        ->assertJsonPath('count', 5)
        ->assertJsonPath('lines.0.tier_applied', true)
        ->assertJsonPath('lines.0.tier_label', 'Tarif dégressif')
        ->assertJsonPath('lines.0.unit_ttc', Money::fromDecimal(108)->format())
        ->assertJsonPath('lines.0.line_ttc', Money::fromDecimal(540)->format())
        ->assertJsonPath('subtotal_ttc', Money::fromDecimal(540)->format());
});

it('adds a product and answers with the cart and a confirmation', function () {
    $product = Product::factory()->priced(100)->create(['name' => 'Toner noir', 'stock_quantity' => 50]);

    $this->postJson(route('cart.store'), ['product' => $product->slug, 'quantite' => 2])
        ->assertOk()
        ->assertJsonPath('count', 2)
        ->assertJsonPath('added', 'Ajouté au panier : Toner noir');

    expect(app(CartService::class)->count())->toBe(2);
});

it('still redirects classic form posts (no JavaScript)', function () {
    $product = Product::factory()->create(['stock_quantity' => 5]);

    $this->post(route('cart.store'), ['product' => $product->slug, 'quantite' => 1])->assertRedirect(route('cart.show'));
});

it('removes a line and answers with the updated cart', function () {
    $keep = Product::factory()->create(['stock_quantity' => 5]);
    $drop = Product::factory()->create(['stock_quantity' => 5]);

    $this->withSession(cartSession([[$keep, 1], [$drop, 2]]))
        ->deleteJson(route('cart.destroy', $drop->slug))
        ->assertOk()
        ->assertJsonPath('count', 1)
        ->assertJsonCount(1, 'lines');
});

it('answers a stock error with a 422 the drawer can roll back from', function () {
    $product = Product::factory()->create(['stock_quantity' => 3]);

    $this->withSession(cartSession([[$product, 1]]))
        ->patchJson(route('cart.update', $product->slug), ['quantite' => 9])
        ->assertStatus(422)
        ->assertJsonValidationErrors('quantite');

    expect(app(CartService::class)->count())->toBe(1);
});

it('rejects bad quantities and unknown products as JSON', function () {
    $product = Product::factory()->create(['stock_quantity' => 3]);

    $this->postJson(route('cart.store'), ['product' => $product->slug, 'quantite' => 0])->assertStatus(422);
    $this->postJson(route('cart.store'), ['product' => 'does-not-exist', 'quantite' => 1])->assertNotFound();
    $this->patchJson(route('cart.update', 'does-not-exist'), ['quantite' => 1])->assertNotFound();
});

it('shows the cash on delivery hint around the limit from the settings', function () {
    $product = Product::factory()->priced(1000)->create(['stock_quantity' => 50]); // 1 200 DH TTC each

    $settings = app(PaymentSettings::class);
    $limit = Money::fromCents($settings->cod_limit_cents);

    $this->withSession(cartSession([[$product, 1]]))->getJson(route('cart.summary'))
        ->assertJsonPath('cod.eligible', true)
        ->assertJsonPath('cod.limit', $limit->format())
        ->assertJsonPath('cod.message', fn (string $message) => str_contains($message, 'Paiement à la livraison disponible') && str_contains($message, $limit->format()));

    $this->withSession(cartSession([[$product, 2]]))->getJson(route('cart.summary'))
        ->assertJsonPath('cod.eligible', false)
        ->assertJsonPath('cod.message', fn (string $message) => str_contains($message, 'virement, dépôt ou chèque') && str_contains($message, $limit->format()));
});

it('is eligible exactly at the limit and not one centime above', function () {
    $settings = app(PaymentSettings::class);
    $settings->cod_limit_cents = 12000;
    $settings->save();

    $atLimit = Product::factory()->priced(100)->create(['stock_quantity' => 5]); // 120,00 TTC
    $this->withSession(cartSession([[$atLimit, 1]]))->getJson(route('cart.summary'))->assertJsonPath('cod.eligible', true);

    $settings->cod_limit_cents = 11999;
    $settings->save();
    $this->withSession(cartSession([[$atLimit, 1]]))->getJson(route('cart.summary'))->assertJsonPath('cod.eligible', false);
});

it('offers the quote link only when its route exists', function () {
    expect(Route::has('quotes.create'))->toBeTrue();
    $this->getJson(route('cart.summary'))->assertJsonPath('urls.quote', route('quotes.create'));
});

it('keeps guest and logged-in carts in their own session', function () {
    $product = Product::factory()->create(['stock_quantity' => 5]);

    $this->withSession(cartSession([[$product, 2]]))->getJson(route('cart.summary'))->assertJsonPath('count', 2);

    $user = User::factory()->customer()->create();
    $this->flushSession();
    $this->actingAs($user)->getJson(route('cart.summary'))->assertJsonPath('count', 0);

    $this->actingAs($user)->postJson(route('cart.store'), ['product' => $product->slug, 'quantite' => 1])->assertOk()->assertJsonPath('count', 1);
    $this->flushSession();
    $this->actingAs($user)->getJson(route('cart.summary'))->assertJsonPath('count', 0);
});

it('requires a CSRF token on cart mutations', function () {
    $product = Product::factory()->create(['stock_quantity' => 5]);
    $this->app['env'] = 'production';

    $this->postJson(route('cart.store'), ['product' => $product->slug, 'quantite' => 1])->assertStatus(419);
    $this->patchJson(route('cart.update', $product->slug), ['quantite' => 1])->assertStatus(419);
    $this->deleteJson(route('cart.destroy', $product->slug))->assertStatus(419);

    $token = 'test-token';
    $this->withSession(['_token' => $token])
        ->postJson(route('cart.store'), ['product' => $product->slug, 'quantite' => 1], ['X-CSRF-TOKEN' => $token])
        ->assertOk();
});

it('renders the drawer as an accessible dialog and keeps the cart icon a plain link', function () {
    $product = Product::factory()->create(['stock_quantity' => 5]);

    $this->withSession(cartSession([[$product, 3]]))->get(route('home'))
        ->assertSee('id="cart-drawer"', false)
        ->assertSee('aria-modal="true"', false)
        ->assertSee('aria-labelledby="cart-drawer-title"', false)
        ->assertSee('href="'.route('cart.show').'"', false)
        ->assertSee('name="csrf-token"', false)
        ->assertSee('data-cart-count="3"', false)
        ->assertSee('>3<', false);
});

it('hooks the product page add-to-cart form to the drawer without losing the normal form', function () {
    $product = Product::factory()->create(['stock_quantity' => 5]);

    $this->get(route('products.show', $product->slug))
        ->assertSee('action="'.route('cart.store').'"', false)
        ->assertSee('cart:add', false)
        ->assertSee('name="product"', false);
});

it('lists the main categories in the empty state', function () {
    $category = Category::factory()->create(['name' => 'Papeterie', 'short_name' => 'Papeterie']);

    $this->get(route('home'))->assertSee(route('categories.show', $category->slug), false);
});
