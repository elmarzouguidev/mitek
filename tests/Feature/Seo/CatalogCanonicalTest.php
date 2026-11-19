<?php

use App\Models\Core\Product\Brand;
use App\Models\Core\Product\Category;
use App\Models\Core\Product\Product;

beforeEach(function () {
    $this->category = Category::factory()->create(['name' => 'Toners']);
    $this->child = Category::factory()->childOf($this->category)->create(['name' => 'HP']);
    Product::factory()->count(2)->for($this->category)->create();
    Product::factory()->for($this->child)->withBrand(Brand::factory()->create(['slug' => 'hp', 'name' => 'HP']))->create();
    $this->base = route('categories.show', $this->category->slug);
});

/**
 * Canonical URL and robots directive of a category listing request.
 *
 * @param  array<string, mixed>  $query
 * @return array{0: string|null, 1: string|null}
 */
function seoOf(mixed $test, string $base, array $query = []): array
{
    $html = $test->get($base.($query === [] ? '' : '?'.http_build_query($query)))->assertOk()->getContent();

    preg_match('#<link rel="canonical" href="(.*?)">#', $html, $canonical);
    preg_match('#<meta name="robots" content="(.*?)">#', $html, $robots);

    return [html_entity_decode($canonical[1] ?? ''), $robots[1] ?? null];
}

it('indexes the clean category URL with itself as canonical', function () {
    expect(seoOf($this, $this->base))->toBe([$this->base, 'index,follow']);
});

it('ignores tracking and unknown parameters in the canonical URL', function () {
    expect(seoOf($this, $this->base, ['utm_source' => 'newsletter', 'fbclid' => 'x']))->toBe([$this->base, 'index,follow']);
});

describe('one filter', function () {
    it('stays indexable and canonical to itself', function (array $query, string $expectedQuery) {
        expect(seoOf($this, $this->base, $query))->toBe([$this->base.'?'.$expectedQuery, 'index,follow']);
    })->with([
        'subcategory' => [['sous_categorie' => 'hp'], 'sous_categorie=hp'],
        'one brand' => [['marques' => ['hp']], 'marques%5B0%5D=hp'],
        'several brands count as one group' => [['marques' => ['hp', 'canon']], 'marques%5B0%5D=hp&marques%5B1%5D=canon'],
        'price range counts as one group' => [['prix_min' => 10, 'prix_max' => 500], 'prix_min=10&prix_max=500'],
        'in stock' => [['en_stock' => 1], 'en_stock=1'],
    ]);

    it('drops unknown parameters from the canonical URL', function () {
        expect(seoOf($this, $this->base, ['en_stock' => 1, 'utm_source' => 'x']))->toBe([$this->base.'?en_stock=1', 'index,follow']);
    });
});

describe('more than one filter', function () {
    it('is noindex,follow with the clean category URL as canonical', function (array $query) {
        expect(seoOf($this, $this->base, $query))->toBe([$this->base, 'noindex,follow']);
    })->with([
        'subcategory and brand' => [['sous_categorie' => 'hp', 'marques' => ['hp']]],
        'brand and price' => [['marques' => ['hp'], 'prix_min' => 10]],
        'price and stock' => [['prix_max' => 500, 'en_stock' => 1]],
        'three groups' => [['sous_categorie' => 'hp', 'marques' => ['hp'], 'en_stock' => 1]],
    ]);
});

describe('sorting', function () {
    it('is noindex,follow with the clean category URL as canonical', function (string $sort) {
        expect(seoOf($this, $this->base, ['tri' => $sort]))->toBe([$this->base, 'noindex,follow']);
    })->with(['prix_croissant', 'prix_decroissant', 'nom', 'nouveautes']);

    it('is noindex even on top of a single filter', function () {
        expect(seoOf($this, $this->base, ['en_stock' => 1, 'tri' => 'nom']))->toBe([$this->base, 'noindex,follow']);
    });

    it('treats the default order, which the filter form submits, as no sorting', function () {
        expect(seoOf($this, $this->base, ['tri' => 'pertinence']))->toBe([$this->base, 'index,follow']);
        expect(seoOf($this, $this->base, ['en_stock' => 1, 'tri' => 'pertinence']))->toBe([$this->base.'?en_stock=1', 'index,follow']);
    });

    it('ignores an unknown sort value', function () {
        expect(seoOf($this, $this->base, ['tri' => 'bogus']))->toBe([$this->base, 'index,follow']);
    });
});

describe('pagination', function () {
    it('keeps the page in the canonical URL of an unfiltered listing', function () {
        Product::factory()->count(13)->for($this->category)->create();

        expect(seoOf($this, $this->base, ['page' => 2]))->toBe([$this->base.'?page=2', 'index,follow']);
    });

    it('keeps the page and the single filter together', function () {
        Product::factory()->count(13)->for($this->category)->create(['stock_quantity' => 5]);

        expect(seoOf($this, $this->base, ['en_stock' => 1, 'page' => 2]))->toBe([$this->base.'?en_stock=1&page=2', 'index,follow']);
    });

    it('sends a filtered, sorted or multi-filter page to the clean category URL', function () {
        Product::factory()->count(13)->for($this->category)->create(['stock_quantity' => 5]);

        expect(seoOf($this, $this->base, ['en_stock' => 1, 'prix_min' => 1, 'page' => 2]))->toBe([$this->base, 'noindex,follow']);
        expect(seoOf($this, $this->base, ['tri' => 'nom', 'page' => 2]))->toBe([$this->base, 'noindex,follow']);
    });
});

it('also applies to subcategory pages', function () {
    $childBase = route('categories.show', $this->child->slug);

    expect(seoOf($this, $childBase))->toBe([$childBase, 'index,follow'])
        ->and(seoOf($this, $childBase, ['en_stock' => 1, 'prix_min' => 5]))->toBe([$childBase, 'noindex,follow']);
});

it('does not block filtered URLs in robots.txt so crawlers can read their noindex', function () {
    $robots = $this->get('/robots.txt')->getContent();

    expect($robots)->not->toContain('Disallow: /categories')->not->toContain('Disallow: /*?')->not->toContain('Disallow: /*tri=');
});
