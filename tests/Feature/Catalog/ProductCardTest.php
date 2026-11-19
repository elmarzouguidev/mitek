<?php

use App\Models\Core\Product\Category;
use App\Models\Core\Product\Product;
use App\Support\Money;

function cardProduct(array $attributes = []): Product
{
    return Product::factory()->priced(100)->create(array_merge(['category_id' => Category::factory()->create()->id, 'reference' => 'REF-1'], $attributes));
}

function addSpecs(Product $product, array $specs): void
{
    foreach (array_values($specs) as $position => [$name, $value]) {
        $product->specifications()->create(['name' => $name, 'value' => $value, 'position' => $position]);
    }
}

it('shows up to three key specifications, skipping the one that repeats the reference', function () {
    $product = cardProduct();
    addSpecs($product, [['Référence constructeur', 'ref-1'], ['Couleur', 'Noir'], ['Type', 'Toner'], ['Rendement', '2 500 pages'], ['Garantie', 'Un an']]);

    $html = $this->get(route('categories.show', $product->category->slug))->getContent();

    expect($html)->toContain('Couleur :')->toContain('Type :')->toContain('Rendement :')
        ->not->toContain('Référence constructeur :')
        ->not->toContain('Garantie :');
});

it('has no specification list when the product has none', function () {
    $product = cardProduct();

    $this->get(route('categories.show', $product->category->slug))->assertDontSee('data-card-specs', false);
});

it('hints at the quantity price when there are tiers', function () {
    $product = cardProduct();
    $product->priceTiers()->create(['min_quantity' => 10, 'price_ht' => Money::fromDecimal(80)]);

    $this->get(route('categories.show', $product->category->slug))
        ->assertSee('Dès '.Money::fromDecimal(96)->format().' TTC l\'unité à partir de 10');

    $plain = cardProduct(['reference' => 'REF-2']);
    $this->get(route('categories.show', $plain->category->slug))->assertDontSee('data-card-tier', false);
});

it('has an add to cart button that posts to the cart without JavaScript and opens the drawer with it', function () {
    $product = cardProduct();

    $this->get(route('categories.show', $product->category->slug))
        ->assertSee('action="'.route('cart.store').'"', false)
        ->assertSee('name="product" value="'.$product->slug.'"', false)
        ->assertSee('name="quantite" value="1"', false)
        ->assertSee('cart:add', false)
        ->assertSee('aria-label="Ajouter '.e($product->name).' au panier"', false);
});

it('disables the button for a product that is out of stock', function () {
    $product = cardProduct(['stock_quantity' => 0]);

    expect($this->get(route('categories.show', $product->category->slug))->getContent())->toMatch('/<button type="submit" disabled[^>]*aria-label="Ajouter/');
});

it('adds the product from a card (classic post)', function () {
    $product = cardProduct(['stock_quantity' => 3]);

    $this->post(route('cart.store'), ['product' => $product->slug, 'quantite' => 1])->assertRedirect(route('cart.show'));
    $this->getJson(route('cart.summary'))->assertJsonPath('count', 1);
});

it('renders the same card on search results and related products without extra loading', function () {
    $category = Category::factory()->create();
    $one = Product::factory()->create(['category_id' => $category->id, 'name' => 'Ramette alpha']);
    $two = Product::factory()->create(['category_id' => $category->id, 'name' => 'Ramette beta']);
    addSpecs($two, [['Format', 'A4']]);

    $this->get(route('search', ['q' => 'ramette']))->assertOk()->assertSee('Format :');
    $this->get(route('products.show', $one->slug))->assertOk()->assertSee('Format :');
});
