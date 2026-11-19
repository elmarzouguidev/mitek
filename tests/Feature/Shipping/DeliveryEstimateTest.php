<?php

use App\Exceptions\ShippingUnavailable;
use App\Models\Core\Product\Product;
use App\Models\Core\Shipping\ShippingZone;
use App\Services\Core\Shipping\DeliveryEstimator;
use App\Settings\ClaimsSettings;
use App\Settings\ShippingSettings;
use App\Support\Money;
use Carbon\CarbonImmutable;
use Database\Seeders\FaqSeeder;
use Database\Seeders\PageSeeder;

function shippingSettings(array $values): void
{
    $settings = app(ShippingSettings::class);
    foreach ($values as $key => $value) {
        $settings->{$key} = $value;
    }
    $settings->save();
}

beforeEach(function () {
    shippingSettings(['dispatch_days' => 2, 'working_days' => [1, 2, 3, 4, 5, 6], 'holidays' => []]);
    $this->casa = ShippingZone::factory()->withCities(['Casablanca', 'Fès'])->withRates([[0, 5000, 25], [5001, null, 60]])->create(['name' => 'Casa', 'transit_min_days' => 1, 'transit_max_days' => 2]);
    $this->rest = ShippingZone::factory()->default()->withRates([[0, null, 45]])->create(['name' => 'Reste', 'transit_min_days' => 2, 'transit_max_days' => 4]);
    $this->estimator = app(DeliveryEstimator::class);
    $this->monday = CarbonImmutable::parse('2026-11-23 10:00'); // a Monday
});

describe('working days', function () {
    it('counts working days and skips the non-working weekday', function () {
        expect($this->estimator->addWorkingDays($this->monday, 2)->toDateString())->toBe('2026-11-25')
            ->and($this->estimator->addWorkingDays(CarbonImmutable::parse('2026-11-27'), 2)->toDateString())->toBe('2026-11-30'); // Fri + Sat + (skip Sun) + Mon
    });

    it('skips holidays', function () {
        shippingSettings(['holidays' => ['2026-11-25']]);

        expect(app(DeliveryEstimator::class)->addWorkingDays($this->monday, 2)->toDateString())->toBe('2026-11-26');
    });

    it('rolls a non-working start day over to the next working day', function () {
        expect($this->estimator->addWorkingDays(CarbonImmutable::parse('2026-11-29'), 0)->toDateString())->toBe('2026-11-30'); // Sunday
    });

    it('respects the working days setting', function () {
        shippingSettings(['working_days' => [1, 2, 3, 4, 5]]);

        expect(app(DeliveryEstimator::class)->addWorkingDays(CarbonImmutable::parse('2026-11-27'), 1)->toDateString())->toBe('2026-11-30'); // Fri + 1 skips Sat and Sun
    });
});

describe('estimate', function () {
    it('adds dispatch and the zone transit to the clock start', function () {
        $estimate = $this->estimator->estimate('Casablanca', 1000, $this->monday);

        // Monday + 2 dispatch + 1..2 transit working days
        expect($estimate->earliest->toDateString())->toBe('2026-11-26')
            ->and($estimate->latest->toDateString())->toBe('2026-11-27')
            ->and($estimate->usedDefaultZone)->toBeFalse()
            ->and($estimate->zone->is($this->casa))->toBeTrue()
            ->and($estimate->rangeLabel())->toContain('nov.')->toContain(' – ');
    });

    it('uses the default zone for an unmapped city and says so', function () {
        $estimate = $this->estimator->estimate('Ifrane', 1000, $this->monday);

        expect($estimate->usedDefaultZone)->toBeTrue()
            ->and($estimate->zone->is($this->rest))->toBeTrue()
            ->and($estimate->earliest->toDateString())->toBe('2026-11-27')
            ->and($estimate->latest->toDateString())->toBe('2026-11-30')
            ->and($estimate->lines()['notice'])->toContain('zone standard');
    });

    it('matches cities without caring about case or accents', function () {
        expect($this->estimator->estimate('FES', 0, $this->monday)->usedDefaultZone)->toBeFalse();
    });

    it('prints a single date when both ends fall on the same day', function () {
        $this->casa->update(['transit_min_days' => 2, 'transit_max_days' => 2]);

        $estimate = $this->estimator->estimate('Casablanca', 0, $this->monday);

        expect($estimate->rangeLabel())->not->toContain('–');
    });

    it('prices the shipping from the grid, VAT included', function () {
        $light = $this->estimator->estimate('Casablanca', 1000, $this->monday);
        $heavy = $this->estimator->estimate('Casablanca', 6000, $this->monday);

        expect($light->shippingTtc->cents)->toBe(Money::fromDecimal(30)->cents) // 25 HT + 20 %
            ->and($heavy->shippingTtc->cents)->toBe(Money::fromDecimal(72)->cents);
    });

    it('still gives dates when no bracket matches the weight', function () {
        $this->rest->rates()->delete();
        $this->rest->rates()->create(['min_weight_g' => 0, 'max_weight_g' => 100, 'price_ht' => Money::fromDecimal(10)]);

        $estimate = $this->estimator->estimate('Ifrane', 9000, $this->monday);

        expect($estimate->shippingTtc)->toBeNull()
            ->and($estimate->lines()['fee'])->toContain('calculés à la commande');
    });

    it('fails when no zone can serve the city', function () {
        ShippingZone::query()->update(['is_active' => false]);

        $this->estimator->estimate('Casablanca', 0, $this->monday);
    })->throws(ShippingUnavailable::class);
});

describe('wording', function () {
    it('derives "24 à 96 h selon la ville" from the active zones', function () {
        expect($this->estimator->summaryText())->toBe('24 à 96 h selon la ville');
    });

    it('ignores inactive zones and follows admin changes', function () {
        $this->rest->update(['is_active' => false]);
        expect($this->estimator->summaryText())->toBe('24 à 48 h selon la ville');

        $this->casa->update(['transit_min_days' => 3, 'transit_max_days' => 3]);
        expect($this->estimator->summaryText())->toBe('72 h selon la ville');
    });

    it('falls back to the setting while no zone is active', function () {
        ShippingZone::query()->update(['is_active' => false]);
        shippingSettings(['delivery_estimate' => '48 à 96 h selon la ville']);

        expect(app(DeliveryEstimator::class)->summaryText())->toBe('48 à 96 h selon la ville');
    });

    it('prints the computed text on the delivery page and in the FAQ, and the dispatch window from the settings', function () {
        $this->seed([PageSeeder::class, FaqSeeder::class]);

        $this->get('/modes-de-livraison')->assertSee('24 à 96 h selon la ville')->assertSee('dans les 2 jours');
        $this->get(route('faq'))->assertSee('24 à 96 h selon la ville');
    });

    it('says "sous 48 h" for two dispatch days and "le jour même" for none', function () {
        expect($this->estimator->dispatchText())->toBe('sous 48 h');

        shippingSettings(['dispatch_days' => 0]);
        expect(app(DeliveryEstimator::class)->dispatchText())->toBe('le jour même');
    });
});

describe('cities', function () {
    it('lists the mapped cities of active zones and refreshes when a zone changes', function () {
        expect($this->estimator->cities())->toBe(['Casablanca', 'Fès']);

        $this->casa->cities()->create(['city' => 'Mohammedia']);
        expect($this->estimator->cities())->toContain('Mohammedia');

        $this->casa->update(['is_active' => false]);
        expect($this->estimator->cities())->toBe([]);
    });
});

describe('endpoint', function () {
    it('returns the window, the fee and the sentences', function () {
        $this->getJson(route('delivery.estimate', ['ville' => 'Casablanca']))
            ->assertOk()
            ->assertHeader('Cache-Control')
            ->assertJsonStructure(['city', 'zone', 'default_zone', 'range', 'earliest', 'latest', 'transit', 'fee', 'lines' => ['estimate', 'transit', 'fee', 'notice', 'hint']])
            ->assertJsonPath('zone', 'Casa')
            ->assertJsonPath('default_zone', false)
            ->assertJsonPath('transit', '1 à 2 jours ouvrés');
    });

    it('prices a product by weight and quantity', function () {
        $product = Product::factory()->create(['weight_grams' => 3000]);

        $this->getJson(route('delivery.estimate', ['ville' => 'Casablanca', 'produit' => $product->slug, 'quantite' => 1]))
            ->assertJsonPath('fee', Money::fromDecimal(30)->format());
        $this->getJson(route('delivery.estimate', ['ville' => 'Casablanca', 'produit' => $product->slug, 'quantite' => 2]))
            ->assertJsonPath('fee', Money::fromDecimal(72)->format());
    });

    it('prices the cart by its weight', function () {
        $product = Product::factory()->create(['weight_grams' => 4000, 'stock_quantity' => 10]);

        $this->withSession(cartSession([[$product, 2]]))
            ->getJson(route('delivery.estimate', ['ville' => 'Casablanca', 'panier' => 1]))
            ->assertJsonPath('fee', Money::fromDecimal(72)->format());
    });

    it('validates its input', function (array $query) {
        $this->getJson(route('delivery.estimate', $query))->assertStatus(422);
    })->with([
        'no city' => [[]],
        'long city' => [['ville' => str_repeat('a', 101)]],
        'unknown product' => [['ville' => 'Casablanca', 'produit' => 'nope']],
        'bad quantity' => [['ville' => 'Casablanca', 'produit' => 'x', 'quantite' => 0]],
    ]);

    it('answers 422 with a message when no zone serves the city', function () {
        ShippingZone::query()->update(['is_active' => false]);

        $this->getJson(route('delivery.estimate', ['ville' => 'Casablanca']))
            ->assertStatus(422)
            ->assertJsonPath('message', fn (string $message) => str_contains($message, 'indisponible'));
    });

    it('is rate limited', function () {
        foreach (range(1, 60) as $attempt) {
            $this->getJson(route('delivery.estimate', ['ville' => 'Casablanca']))->assertOk();
        }

        $this->getJson(route('delivery.estimate', ['ville' => 'Casablanca']))->assertStatus(429);
    });
});

describe('pages', function () {
    it('shows "Comment l\'obtenir" on the product page, with the dispatch window and no estimate before a city', function () {
        $product = Product::factory()->create();

        $this->get(route('products.show', $product->slug))
            ->assertSee('Comment l\'obtenir')
            ->assertSee('Livraison partout au Maroc')
            ->assertSee('Expédié sous 48 h après validation du paiement')
            ->assertSee('Paiement à la livraison disponible jusqu\'à '.Money::fromCents(200000)->format().' TTC')
            ->assertSee('<option value="Casablanca">', false)
            ->assertDontSee('Livraison estimée');
    });

    it('renders the estimate on the server when a city is given (no JavaScript)', function () {
        $product = Product::factory()->create(['weight_grams' => 1000]);

        $this->get(route('products.show', ['slug' => $product->slug, 'ville' => 'Casablanca']))
            ->assertSee('Livraison estimée')
            ->assertSee('vers Casablanca')
            ->assertSee('1 à 2 jours ouvrés')
            ->assertSee(Money::fromDecimal(30)->format().' TTC')
            ->assertDontSee('zone standard');

        $this->get(route('products.show', ['slug' => $product->slug, 'ville' => 'Ifrane']))->assertSee('zone standard');
    });

    it('escapes the city', function () {
        $product = Product::factory()->create();

        $this->get(route('products.show', ['slug' => $product->slug, 'ville' => '<script>alert(1)</script>']))
            ->assertDontSee('<script>alert(1)</script>', false);
    });

    it('shows the Casablanca pickup line only when the claim is enabled', function () {
        $product = Product::factory()->create();
        $this->get(route('products.show', $product->slug))->assertDontSee('Retrait possible à Casablanca');

        $claims = app(ClaimsSettings::class);
        $claims->pickup_casablanca = true;
        $claims->save();

        $this->get(route('products.show', $product->slug))->assertSee('Retrait possible à Casablanca');
    });

    it('shows the estimate on the cart page with the cart weight', function () {
        $product = Product::factory()->create(['weight_grams' => 4000, 'stock_quantity' => 10]);

        $this->withSession(cartSession([[$product, 2]]))->get(route('cart.show', ['ville' => 'Casablanca']))
            ->assertSee('Expédié sous 48 h après validation du paiement')
            ->assertSee('Livraison estimée')
            ->assertSee(Money::fromDecimal(72)->format().' TTC');
    });
});
