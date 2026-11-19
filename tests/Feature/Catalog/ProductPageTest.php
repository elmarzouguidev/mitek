<?php

use App\Models\Core\Product\Category;
use App\Models\Core\Product\CompatibleModel;
use App\Models\Core\Product\Product;
use App\Support\Money;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

it('shows the TTC price with the HT price and the product details', function () {
    $product = Product::factory()->priced('790')->create([
        'name' => 'Toner HP 312A noir',
        'reference' => 'CF380A',
        'excerpt' => 'Toner original.',
    ]);

    $this->get(route('products.show', $product->slug))
        ->assertOk()
        ->assertSee('Toner HP 312A noir')
        ->assertSee('CF380A')
        ->assertSee(Money::fromDecimal(948)->format(), false)
        ->assertSee(Money::fromDecimal(790)->format(), false)
        ->assertSee('TTC')
        ->assertSee('HT');
});

it('shows the tier price table', function () {
    $product = Product::factory()->priced(100)->withTiers([5 => 90, 20 => 80])->create();

    $this->get(route('products.show', $product->slug))
        ->assertSee('Tarifs dégressifs')
        ->assertSee('1 – 4')
        ->assertSee('5 – 19')
        ->assertSee('20 et +')
        ->assertSee(Money::fromDecimal(108)->format(), false)
        ->assertSee(Money::fromDecimal(96)->format(), false);
});

it('does not show a tier table for a product without tiers', function () {
    $product = Product::factory()->create();

    $this->get(route('products.show', $product->slug))->assertDontSee('Tarifs dégressifs');
});

it('shows specifications and compatible printers', function () {
    $product = Product::factory()->create();
    $product->specifications()->createMany([
        ['name' => 'Couleur', 'value' => 'Noir', 'position' => 0],
        ['name' => 'Type', 'value' => "Toner d'origine", 'position' => 1],
    ]);
    $product->compatibleModels()->attach(CompatibleModel::factory()->create(['name' => 'LaserJet M476']));

    $this->get(route('products.show', $product->slug))
        ->assertSee('Caractéristiques')
        ->assertSeeInOrder(['Couleur', 'Noir', 'Type'])
        ->assertSee('Imprimantes compatibles')
        ->assertSee('LaserJet M476')
        ->assertSee(route('search', ['q' => 'LaserJet M476']), false);
});

it('shows the stock status and disables the cart button when sold out', function () {
    $available = Product::factory()->create(['stock_quantity' => 30]);
    $soldOut = Product::factory()->outOfStock()->create();

    $this->get(route('products.show', $available->slug))->assertSee('En stock')->assertDontSee('type="submit" disabled', false);
    $this->get(route('products.show', $soldOut->slug))->assertSee('Rupture de stock')->assertSee('type="submit" disabled', false);
});

it('suggests related products from the same category, never the product itself', function () {
    $category = Category::factory()->create();
    $product = Product::factory()->for($category)->create(['name' => 'Main Product']);
    Product::factory()->for($category)->create(['name' => 'Sibling Product']);
    Product::factory()->for($category)->inactive()->create(['name' => 'Hidden Sibling']);
    Product::factory()->create(['name' => 'Unrelated Product']);

    $this->get(route('products.show', $product->slug))
        ->assertSee('Produits similaires')
        ->assertSee('Sibling Product')
        ->assertDontSee('Hidden Sibling')
        ->assertDontSee('Unrelated Product');
});

it('uses the SEO fields for the title and description with sensible fallbacks', function () {
    $custom = Product::factory()->create(['name' => 'Plain Name', 'meta_title' => 'Custom SEO Title', 'meta_description' => 'Custom SEO description.']);
    $fallback = Product::factory()->create(['name' => 'Fallback Name', 'excerpt' => 'Fallback excerpt.']);

    $this->get(route('products.show', $custom->slug))
        ->assertSee('<title>Custom SEO Title · Mitek.ma</title>', false)
        ->assertSee('<meta name="description" content="Custom SEO description.">', false);

    $this->get(route('products.show', $fallback->slug))
        ->assertSee('<title>Fallback Name · Mitek.ma</title>', false)
        ->assertSee('<meta name="description" content="Fallback excerpt.">', false);
});

it('renders a breadcrumb through the parent category', function () {
    $parent = Category::factory()->create(['name' => 'Famille']);
    $child = Category::factory()->childOf($parent)->create(['name' => 'Sous-famille']);
    $product = Product::factory()->for($child)->create(['name' => 'Article']);

    $this->get(route('products.show', $product->slug))
        ->assertSeeInOrder(['Accueil', 'Famille', 'Sous-famille', 'Article'])
        ->assertSee(route('categories.show', $parent->slug), false);
});

it('uses the product name as the alt text of its images and falls back to a placeholder', function () {
    Storage::fake('public');
    $withImage = Product::factory()->create(['name' => 'Photographed Item']);
    $withImage->addMedia(UploadedFile::fake()->image('item.jpg', 900, 900))->toMediaCollection(Product::GALLERY);
    $withoutImage = Product::factory()->create(['name' => 'Bare Item']);

    $this->get(route('products.show', $withImage->slug))->assertSee('alt="Photographed Item"', false);
    $this->get(route('products.show', $withoutImage->slug))->assertSee('aria-label="Bare Item"', false);
});

it('generates thumb, card and large WebP conversions for gallery images', function () {
    Storage::fake('public');
    $product = Product::factory()->create();

    $media = $product->addMedia(UploadedFile::fake()->image('item.jpg', 1600, 1600))->toMediaCollection(Product::GALLERY);

    expect($media->getUrl('thumb'))->toEndWith('.webp')
        ->and($media->getUrl('card'))->toEndWith('.webp')
        ->and($media->getUrl('large'))->toEndWith('.webp');
    Storage::disk('public')->assertExists([
        $media->getPathRelativeToRoot('thumb'),
        $media->getPathRelativeToRoot('card'),
        $media->getPathRelativeToRoot('large'),
    ]);
});

it('does not overwrite files with the same name uploaded in the same month', function () {
    Storage::fake('public');
    $product = Product::factory()->create();

    $first = $product->addMedia(UploadedFile::fake()->image('photo.jpg'))->toMediaCollection(Product::GALLERY);
    $second = $product->addMedia(UploadedFile::fake()->image('photo.jpg'))->toMediaCollection(Product::GALLERY);

    expect($first->getPathRelativeToRoot())->not->toBe($second->getPathRelativeToRoot());
    Storage::disk('public')->assertExists([$first->getPathRelativeToRoot(), $second->getPathRelativeToRoot()]);
});
