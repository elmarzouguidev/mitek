<?php

use App\Models\Core\Product\Brand;
use App\Models\Core\Product\Category;
use App\Models\Core\Product\Product;
use Illuminate\Database\QueryException;

it('generates slugs from names', function () {
    expect(Product::factory()->create(['name' => 'Toner HP 312A noir'])->slug)->toBe('toner-hp-312a-noir')
        ->and(Category::factory()->create(['name' => 'Équipements & Accessoires'])->slug)->toBe('equipements-accessoires')
        ->and(Brand::factory()->create(['name' => 'Grandstream'])->slug)->toBe('grandstream');
});

it('keeps product slugs unique when names collide', function () {
    $first = Product::factory()->create(['name' => 'Stylo gel']);
    $second = Product::factory()->create(['name' => 'Stylo gel']);
    $third = Product::factory()->create(['name' => 'Stylo gel']);

    expect([$first->slug, $second->slug, $third->slug])->toBe(['stylo-gel', 'stylo-gel-1', 'stylo-gel-2']);
});

it('keeps category slugs unique when names collide', function () {
    $first = Category::factory()->create(['name' => 'Papeterie']);
    $second = Category::factory()->create(['name' => 'Papeterie']);

    expect($first->slug)->not->toBe($second->slug);
});

it('does not change the slug when a product is renamed', function () {
    $product = Product::factory()->create(['name' => 'Ancien nom']);

    $product->update(['name' => 'Nouveau nom']);

    expect($product->fresh()->slug)->toBe('ancien-nom');
});

it('does not reuse the slug of a soft deleted product', function () {
    $deleted = Product::factory()->create(['name' => 'Chaise']);
    $deleted->delete();

    $replacement = Product::factory()->create(['name' => 'Chaise']);

    expect($replacement->slug)->not->toBe($deleted->slug);
});

it('rejects duplicate references at the database level', function () {
    Product::factory()->create(['reference' => 'CF380A']);

    Product::factory()->create(['reference' => 'CF380A']);
})->throws(QueryException::class);
