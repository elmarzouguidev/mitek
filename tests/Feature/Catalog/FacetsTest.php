<?php

use App\Models\Core\Product\Brand;
use App\Models\Core\Product\Category;
use App\Models\Core\Product\CompatibleModel;
use App\Models\Core\Product\Product;
use App\Services\Core\Product\CatalogFilters;
use App\Services\Core\Product\ProductCatalog;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $this->parent = Category::factory()->create(['name' => 'Toners']);
    $this->hpCat = Category::factory()->childOf($this->parent)->create(['name' => 'Toners HP']);
    $this->canonCat = Category::factory()->childOf($this->parent)->create(['name' => 'Toners Canon']);
    $this->hp = Brand::factory()->create(['name' => 'HP']);
    $this->canon = Brand::factory()->create(['name' => 'Canon']);
    $this->m476 = CompatibleModel::factory()->create(['name' => 'HP LaserJet M476']);
    $this->p1102 = CompatibleModel::factory()->create(['name' => 'HP LaserJet P1102']);
    $this->lbp = CompatibleModel::factory()->create(['name' => 'Canon LBP6000']);

    $this->a = Product::factory()->withBrand($this->hp)->create(['category_id' => $this->hpCat->id, 'stock_quantity' => 5]);
    $this->b = Product::factory()->withBrand($this->hp)->create(['category_id' => $this->hpCat->id, 'stock_quantity' => 0]);
    $this->c = Product::factory()->withBrand($this->canon)->create(['category_id' => $this->canonCat->id, 'stock_quantity' => 5]);
    $this->a->compatibleModels()->attach([$this->m476->id, $this->p1102->id]);
    $this->b->compatibleModels()->attach($this->m476);
    $this->c->compatibleModels()->attach($this->lbp);

    $this->catalog = app(ProductCatalog::class);
    $this->tree = Category::with('children')->findOrFail($this->parent->id);
});

function facets(CatalogFilters $filters = new CatalogFilters): array
{
    return app(ProductCatalog::class)->facetCounts(Category::with('children')->where('name', 'Toners')->firstOrFail(), $filters);
}

describe('counts', function () {
    it('counts subcategories, brands, printers and stock for the whole category', function () {
        $facets = facets();

        expect($facets['subcategories'])->toBe([$this->hpCat->id => 2, $this->canonCat->id => 1])
            ->and($facets['brands'])->toBe([$this->hp->id => 2, $this->canon->id => 1])
            ->and($facets['printers'])->toBe(['Canon LBP6000' => 1, 'HP LaserJet M476' => 2, 'HP LaserJet P1102' => 1])
            ->and($facets['in_stock'])->toBe(2);
    });

    it('lets a facet ignore its own selection but respect the others', function () {
        $facets = facets(new CatalogFilters(brandSlugs: [$this->hp->slug]));

        // brands ignore the brand filter...
        expect($facets['brands'])->toBe([$this->hp->id => 2, $this->canon->id => 1])
            // ...everything else only counts HP products
            ->and($facets['subcategories'])->toBe([$this->hpCat->id => 2])
            ->and($facets['printers'])->toBe(['HP LaserJet M476' => 2, 'HP LaserJet P1102' => 1])
            ->and($facets['in_stock'])->toBe(1);
    });

    it('matches the number of results you get by ticking a value', function () {
        $filters = new CatalogFilters(printerNames: ['HP LaserJet P1102']);
        $results = $this->catalog->listing($this->tree, $filters)->count();

        expect($results)->toBe(facets()['printers']['HP LaserJet P1102'])->toBe(1);

        $withStock = new CatalogFilters(inStockOnly: true, printerNames: ['HP LaserJet M476']);
        expect($this->catalog->listing($this->tree, $withStock)->count())->toBe(facets(new CatalogFilters(inStockOnly: true))['printers']['HP LaserJet M476']);
    });

    it('applies the price range and the in-stock filter to the other counts', function () {
        $this->a->update(['price_ht' => 100_00]);
        $this->b->update(['price_ht' => 100_00]);
        $this->c->update(['price_ht' => 900_00]);

        $facets = facets(new CatalogFilters(minPriceDh: 500));

        expect($facets['brands'])->not->toHaveKey($this->hp->id)->and($facets['brands'])->toHaveKey($this->canon->id);

        $stock = facets(new CatalogFilters(inStockOnly: true));
        expect($stock['subcategories'])->toBe([$this->hpCat->id => 1, $this->canonCat->id => 1])
            ->and($stock['in_stock'])->toBe(2);
    });

    it('never counts hidden products', function () {
        Product::factory()->inactive()->withBrand($this->hp)->create(['category_id' => $this->hpCat->id])->compatibleModels()->attach($this->m476);
        Product::factory()->withBrand($this->hp)->create(['category_id' => Category::factory()->inactive()->childOf($this->parent)->create()->id]);

        expect(facets()['brands'][$this->hp->id])->toBe(2)->and(facets()['printers']['HP LaserJet M476'])->toBe(2);
    });

    it('lists the printer models of the category tree whatever the other filters', function () {
        expect($this->catalog->printersFor($this->tree))->toBe(['Canon LBP6000' => 1, 'HP LaserJet M476' => 2, 'HP LaserJet P1102' => 1]);
    });
});

describe('printer filter', function () {
    it('keeps products compatible with any selected model', function () {
        $url = route('categories.show', ['slug' => $this->parent->slug, 'imprimantes' => ['HP LaserJet P1102', 'Canon LBP6000']]);

        $names = collect($this->get($url)->assertOk()->viewData('products')->items())->pluck('id')->sort()->values()->all();

        expect($names)->toBe(collect([$this->a->id, $this->c->id])->sort()->values()->all());
    });

    it('shows the printer facet with counts, and only for categories that have compatible models', function () {
        $this->get(route('categories.show', $this->parent->slug))
            ->assertSee('Imprimante compatible')
            ->assertSee('HP LaserJet M476')
            ->assertSee('(2)', false);

        $plain = Category::factory()->create();
        Product::factory()->create(['category_id' => $plain->id]);
        $this->get(route('categories.show', $plain->slug))->assertDontSee('Imprimante compatible');
    });

    it('shows subcategory, brand and stock counts in the sidebar', function () {
        $html = $this->get(route('categories.show', $this->parent->slug))->getContent();

        expect($html)->toContain('Toners HP <span class="text-slate-400" data-facet-count>(2)</span>')
            ->toContain('HP <span class="text-slate-400" data-facet-count>(2)</span>')
            ->toContain('data-facet-count>(2)</span></label>'); // in stock
    });

    it('keeps a selected value visible even when it no longer matches anything', function () {
        $html = $this->get(route('categories.show', ['slug' => $this->parent->slug, 'imprimantes' => ['Canon LBP6000'], 'marques' => [$this->hp->slug]]))->getContent();

        expect($html)->toContain('value="Canon LBP6000" checked');
    });

    it('is noindex with the clean category as canonical', function () {
        $this->get(route('categories.show', ['slug' => $this->parent->slug, 'imprimantes' => ['Canon LBP6000']]))
            ->assertSee('<meta name="robots" content="noindex,follow">', false)
            ->assertSee('<link rel="canonical" href="'.route('categories.show', $this->parent->slug).'">', false);
    });

    it('ignores unusable printer values instead of failing', function () {
        $this->get(route('categories.show', ['slug' => $this->parent->slug, 'imprimantes' => ['', str_repeat('x', 200), ['nested']]]))->assertOk();
    });

    it('renders every value without JavaScript, with the extra ones behind "Voir plus"', function () {
        foreach (range(1, 10) as $i) {
            $brand = Brand::factory()->create(['name' => 'Marque '.$i]);
            Product::factory()->withBrand($brand)->create(['category_id' => $this->hpCat->id]);
        }

        $html = $this->get(route('categories.show', $this->parent->slug))->getContent();

        expect(substr_count($html, 'name="marques[]"'))->toBe(12)
            ->and($html)->toContain('Voir plus (4)')
            ->and($html)->toContain('type="search" x-model="q"');
    });
});

describe('queries', function () {
    it('keeps the number of queries flat as products, brands and printers grow', function () {
        $count = function () {
            DB::flushQueryLog();
            DB::enableQueryLog();
            $this->get(route('categories.show', $this->parent->slug))->assertOk();
            $total = count(DB::getQueryLog());
            DB::disableQueryLog();

            return $total;
        };

        $count();
        $before = $count();

        foreach (range(1, 8) as $i) {
            $product = Product::factory()->withBrand(Brand::factory()->create())->withTiers([3 => 5])->create(['category_id' => $this->hpCat->id]);
            $product->compatibleModels()->attach(CompatibleModel::factory()->create());
            $product->specifications()->create(['name' => 'Couleur', 'value' => 'Noir', 'position' => 0]);
        }
        $count();

        expect($count())->toBe($before);
    });
});
