<?php

use App\Models\Core\Product\Category;
use App\Models\Core\Product\Product;
use App\Models\User\User;
use App\Services\Core\Product\PriceCalculator;
use App\Services\Core\Product\ProductBadges;
use App\Settings\CatalogSettings;
use App\Support\Money;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;

function promoProduct(string|int|float $price, string|int|float $compareAt, ?string $endsAt = null, array $attributes = []): Product
{
    return Product::factory()->priced($price)->onPromotion($compareAt, $endsAt)->create($attributes);
}

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function promoPayload(array $overrides = []): array
{
    return array_merge([
        'category_id' => Category::factory()->create()->id,
        'reference' => 'PROMO-1',
        'name' => 'Toner promo',
        'price_ht' => '80',
        'stock_quantity' => 10,
        'low_stock_threshold' => 5,
        'weight_grams' => 500,
        'is_active' => true,
    ], $overrides);
}

function badgeKeys(Product $product): array
{
    return array_column(app(ProductBadges::class)->for($product->fresh()->load('priceTiers')), 'key');
}

describe('price calculator', function () {
    it('has no promotion without a previous price', function () {
        expect(app(PriceCalculator::class)->promo(Product::factory()->create()->load('priceTiers')))->toBeNull();
    });

    it('ignores a previous price that is not higher than the current one', function () {
        $same = promoProduct(100, 100);
        $lower = promoProduct(100, 80);

        expect(app(PriceCalculator::class)->promo($same))->toBeNull()
            ->and(app(PriceCalculator::class)->promo($lower))->toBeNull();
    });

    it('computes the previous price TTC, the saving and the percentage', function () {
        $promo = app(PriceCalculator::class)->promo(promoProduct(80, 100)); // 20 % VAT

        expect($promo->compareAtHt->cents)->toBe(Money::fromDecimal(100)->cents)
            ->and($promo->compareAtTtc->cents)->toBe(Money::fromDecimal(120)->cents)
            ->and($promo->savingTtc->cents)->toBe(Money::fromDecimal(24)->cents) // 120 - 96
            ->and($promo->percent)->toBe(20)
            ->and($promo->endsAt)->toBeNull();
    });

    it('rounds the percentage and never shows less than 1 %', function () {
        expect(app(PriceCalculator::class)->promo(promoProduct(2, 3))->percent)->toBe(33)
            ->and(app(PriceCalculator::class)->promo(promoProduct('99.99', 100))->percent)->toBe(1);
    });

    it('uses the VAT rate of the product', function () {
        $promo = app(PriceCalculator::class)->promo(promoProduct(80, 100, null, ['vat_rate' => 10]));

        expect($promo->compareAtTtc->cents)->toBe(Money::fromDecimal(110)->cents);
    });

    it('stops at the end date', function () {
        $running = promoProduct(80, 100, now()->addHour()->toDateTimeString());
        $ended = promoProduct(80, 100, now()->subMinute()->toDateTimeString());

        expect(app(PriceCalculator::class)->promo($running)->endsAt)->not->toBeNull()
            ->and(app(PriceCalculator::class)->promo($ended))->toBeNull();
    });
});

describe('scopes', function () {
    it('finds running promotions only', function () {
        $running = promoProduct(80, 100);
        $timed = promoProduct(80, 100, now()->addDay()->toDateTimeString());
        promoProduct(80, 100, now()->subDay()->toDateTimeString());
        promoProduct(100, 100);
        Product::factory()->create();

        expect(Product::onPromotion()->pluck('id')->sort()->values()->all())->toBe(collect([$running->id, $timed->id])->sort()->values()->all());
    });

    it('finds best sellers and recent products', function () {
        $best = Product::factory()->bestSeller()->create();
        $old = Product::factory()->create(['created_at' => now()->subDays(90)]);

        expect(Product::bestSellers()->pluck('id')->all())->toBe([$best->id])
            ->and(Product::addedWithin(30)->pluck('id')->all())->toBe([$best->id])
            ->and(Product::addedWithin(120)->pluck('id')->all())->toContain($old->id);
    });
});

describe('badges', function () {
    beforeEach(function () {
        $this->settings = app(CatalogSettings::class);
    });

    it('shows the promotion percentage, in order: promotion, top vente, nouveau', function () {
        $product = promoProduct(80, 100, null, ['is_best_seller' => true]);

        expect(badgeKeys($product))->toBe(['promo', 'best_seller', 'new'])
            ->and(app(ProductBadges::class)->for($product->load('priceTiers'))[0]['label'])->toBe('−20 %');
    });

    it('drops the badges that no longer apply', function () {
        $product = promoProduct(80, 100, now()->subHour()->toDateTimeString(), ['created_at' => now()->subDays(60)]);

        expect(badgeKeys($product))->toBe([]);
    });

    it('follows the "Nouveau" duration from the settings, and 0 hides it', function () {
        $product = Product::factory()->create(['created_at' => now()->subDays(10)]);

        $this->settings->new_badge_days = 5;
        $this->settings->save();
        expect(badgeKeys($product))->toBe([]);

        $this->settings->new_badge_days = 15;
        $this->settings->save();
        expect(badgeKeys($product))->toBe(['new']);

        $this->settings->new_badge_days = 0;
        $this->settings->save();
        expect(badgeKeys(Product::factory()->create()))->toBe([]);
    });
});

describe('storefront', function () {
    it('shows the badge, the struck price and the current price on listing cards', function () {
        $category = Category::factory()->create();
        promoProduct(80, 100, null, ['category_id' => $category->id, 'name' => 'Toner promo']);
        Product::factory()->priced(50)->create(['category_id' => $category->id, 'name' => 'Toner normal']);

        $html = $this->get(route('categories.show', $category->slug))->assertOk()->assertSee('−20 %')->getContent();

        expect($html)->toContain('<s>'.Money::fromDecimal(120)->format().'</s>')
            ->and(substr_count($html, '<s>'))->toBe(1)
            ->and($html)->toContain('data-badge="promo"');
    });

    it('shows the saving, the previous price and the end date on the product page', function () {
        $product = promoProduct(80, 100, '2030-12-31 23:59:00');

        $this->get(route('products.show', $product->slug))
            ->assertSee('<s>'.Money::fromDecimal(120)->format().'</s>', false)
            ->assertSee('−20 %')
            ->assertSee('Économisez '.Money::fromDecimal(24)->format())
            ->assertSee('Offre jusqu\'au 31 décembre 2030 à 23:59')
            ->assertSee('x-data="countdown(', false)
            ->assertSee('x-show="!quote.tier_applied"', false);
    });

    it('has no countdown for a promotion without an end date, and no promo block without a promotion', function () {
        $open = promoProduct(80, 100);
        $this->get(route('products.show', $open->slug))->assertSee('Économisez')->assertDontSee('countdown(', false);

        $plain = Product::factory()->create();
        $this->get(route('products.show', $plain->slug))->assertDontSee('Économisez')->assertDontSee('<s>', false);
    });

    it('prints the top vente badge on cards', function () {
        $product = Product::factory()->bestSeller()->create();

        $this->get(route('categories.show', $product->category->slug))->assertSee('Top vente');
    });

    it('adds priceValidUntil to the structured data only for a timed promotion, with the current price', function () {
        $timed = promoProduct(80, 100, '2030-12-31 23:59:00');
        $open = promoProduct(80, 100);

        $this->get(route('products.show', $timed->slug))
            ->assertSee('"priceValidUntil":"2030-12-31"', false)
            ->assertSee('"price":"96.00"', false);
        $this->get(route('products.show', $open->slug))->assertDontSee('priceValidUntil', false);
    });

    it('keeps the query count flat as promotional cards are added', function () {
        $count = function (string $url) {
            DB::flushQueryLog();
            DB::enableQueryLog();
            $this->get($url)->assertOk();
            $total = count(DB::getQueryLog());
            DB::disableQueryLog();

            return $total;
        };

        $category = Category::factory()->create();
        promoProduct(80, 100, null, ['category_id' => $category->id, 'is_best_seller' => true]);
        $count(route('categories.show', $category->slug));
        $one = $count(route('categories.show', $category->slug));

        foreach (range(1, 6) as $i) {
            promoProduct(80, 100, now()->addDays($i)->toDateTimeString(), ['category_id' => $category->id, 'is_best_seller' => true]);
        }
        $count(route('categories.show', $category->slug));

        expect($count(route('categories.show', $category->slug)))->toBe($one);
    });
});

describe('admin', function () {
    beforeEach(function () {
        $this->admin = User::factory()->admin()->create();
    });

    it('saves a promotion and the best seller flag, and exposes them to the form', function () {
        $this->actingAs($this->admin)->post(route('admin.products.store'), promoPayload([
            'price_ht' => '80',
            'compare_at_price_ht' => '100.50',
            'promo_ends_at' => '2030-12-31T23:59',
            'is_best_seller' => true,
        ]))->assertSessionHasNoErrors();

        $product = Product::firstOrFail();
        expect($product->compare_at_price_ht->cents)->toBe(Money::fromDecimal('100.50')->cents)
            ->and($product->promo_ends_at->format('Y-m-d H:i'))->toBe('2030-12-31 23:59')
            ->and($product->is_best_seller)->toBeTrue();

        $this->actingAs($this->admin)->get(route('admin.products.edit', $product))
            ->assertInertia(fn (Assert $page) => $page
                ->where('product.compare_at_price_ht', 100.5)
                ->where('product.promo_ends_at', '2030-12-31T23:59')
                ->where('product.is_best_seller', true));
    });

    it('requires the previous price to be higher than the current price', function (string $compareAt) {
        $this->actingAs($this->admin)->post(route('admin.products.store'), promoPayload(['price_ht' => '80', 'compare_at_price_ht' => $compareAt]))
            ->assertSessionHasErrors('compare_at_price_ht');

        expect(Product::count())->toBe(0);
    })->with(['equal' => '80', 'lower' => '50']);

    it('rejects an invalid end date', function () {
        $this->actingAs($this->admin)->post(route('admin.products.store'), promoPayload(['compare_at_price_ht' => '100', 'promo_ends_at' => 'tomorrow-ish']))
            ->assertSessionHasErrors('promo_ends_at');
    });

    it('clears the end date together with the previous price', function () {
        $product = promoProduct(80, 100, '2030-12-31 23:59:00');

        $this->actingAs($this->admin)->put(route('admin.products.update', $product), promoPayload([
            'category_id' => $product->category_id,
            'reference' => $product->reference,
            'price_ht' => '80',
            'compare_at_price_ht' => '',
            'promo_ends_at' => '2030-12-31T23:59',
        ]))->assertSessionHasNoErrors();

        $product->refresh();
        expect($product->compare_at_price_ht)->toBeNull()->and($product->promo_ends_at)->toBeNull();
    });

    it('validates the "Nouveau" duration setting', function (mixed $days, bool $valid) {
        $response = $this->actingAs($this->admin)->put(route('admin.settings.catalog'), ['best_seller_min_sales' => 0, 'new_badge_days' => $days]);

        $valid ? $response->assertSessionHasNoErrors() : $response->assertSessionHasErrors('new_badge_days');
    })->with([[0, true], [30, true], [365, true], [-1, false], [366, false], ['x', false], [null, false]]);
});
