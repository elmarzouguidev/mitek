<?php

use App\Models\Core\Shipping\ShippingZone;
use App\Models\User\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->admin = User::factory()->admin()->create();
});

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function zonePayload(array $overrides = []): array
{
    return array_merge([
        'name' => 'Casablanca et environs',
        'is_default' => false,
        'is_active' => true,
        'cities' => ['Casablanca', 'Mohammedia'],
        'rates' => [
            ['min_weight_g' => 0, 'max_weight_g' => 5000, 'price_ht' => '25'],
            ['min_weight_g' => 5001, 'max_weight_g' => null, 'price_ht' => '60.50'],
        ],
    ], $overrides);
}

it('keeps shipping management away from guests and customers', function () {
    $zone = ShippingZone::factory()->flatRate()->create();
    $customer = User::factory()->customer()->create();

    foreach ([['get', route('admin.shipping.index')], ['post', route('admin.shipping.store')], ['put', route('admin.shipping.update', $zone)], ['delete', route('admin.shipping.destroy', $zone)]] as [$method, $url]) {
        $this->$method($url)->assertRedirect(route('login'));
        $this->actingAs($customer)->$method($url)->assertForbidden();
        auth()->logout();
    }
});

it('creates a zone with cities and weight rates stored in centimes', function () {
    $this->actingAs($this->admin)->post(route('admin.shipping.store'), zonePayload())
        ->assertRedirect(route('admin.shipping.index'))
        ->assertSessionHas('success');

    $zone = ShippingZone::with(['cities', 'rates'])->firstOrFail();

    expect($zone->cities->pluck('city')->all())->toBe(['Casablanca', 'Mohammedia'])
        ->and($zone->cities->pluck('normalized')->all())->toBe(['casablanca', 'mohammedia'])
        ->and($zone->rates->map(fn ($rate) => [$rate->min_weight_g, $rate->max_weight_g, $rate->price_ht->cents])->all())
        ->toBe([[0, 5000, 2500], [5001, null, 6050]]);
});

it('validates the zone', function (array $overrides, string|array $errors) {
    $this->actingAs($this->admin)->post(route('admin.shipping.store'), zonePayload($overrides))->assertSessionHasErrors($errors);

    expect(ShippingZone::count())->toBe(0);
})->with([
    'name required' => [['name' => ''], 'name'],
    'at least one rate' => [['rates' => []], 'rates'],
    'negative price' => [['rates' => [['min_weight_g' => 0, 'max_weight_g' => null, 'price_ht' => '-1']]], 'rates.0.price_ht'],
    'maximum below minimum' => [['rates' => [['min_weight_g' => 500, 'max_weight_g' => 100, 'price_ht' => '10']]], 'rates.0.max_weight_g'],
    'overlapping brackets' => [['rates' => [['min_weight_g' => 0, 'max_weight_g' => 5000, 'price_ht' => '10'], ['min_weight_g' => 4000, 'max_weight_g' => null, 'price_ht' => '20']]], 'rates.1.min_weight_g'],
    'bracket after an open ended one' => [['rates' => [['min_weight_g' => 0, 'max_weight_g' => null, 'price_ht' => '10'], ['min_weight_g' => 9000, 'max_weight_g' => 10000, 'price_ht' => '20']]], 'rates.1.min_weight_g'],
    'duplicate city in the list' => [['cities' => ['Fès', 'fes']], 'cities.1'],
]);

it('accepts contiguous brackets in any order', function () {
    $this->actingAs($this->admin)->post(route('admin.shipping.store'), zonePayload(['rates' => [
        ['min_weight_g' => 5001, 'max_weight_g' => null, 'price_ht' => '60'],
        ['min_weight_g' => 0, 'max_weight_g' => 5000, 'price_ht' => '25'],
    ]]))->assertSessionHasNoErrors();
});

it('refuses a city that already belongs to another zone, ignoring accents and case', function () {
    ShippingZone::factory()->withCities(['Fès'])->flatRate()->create();

    $this->actingAs($this->admin)->post(route('admin.shipping.store'), zonePayload(['cities' => ['FES']]))->assertSessionHasErrors('cities.0');
});

it('lets a zone keep its own cities when edited', function () {
    $zone = ShippingZone::factory()->withCities(['Fès'])->flatRate()->create(['name' => 'North']);

    $this->actingAs($this->admin)->put(route('admin.shipping.update', $zone), zonePayload(['name' => 'North renamed', 'cities' => ['Fès', 'Meknès']]))
        ->assertSessionHasNoErrors();

    expect($zone->fresh()->name)->toBe('North renamed')
        ->and($zone->cities()->count())->toBe(2);
});

it('replaces the cities and rates on update', function () {
    $zone = ShippingZone::factory()->withCities(['Fès', 'Taza'])->withRates([[0, null, 99]])->create();

    $this->actingAs($this->admin)->put(route('admin.shipping.update', $zone), zonePayload(['cities' => ['Oujda']]));

    expect($zone->cities()->pluck('city')->all())->toBe(['Oujda'])
        ->and($zone->rates()->count())->toBe(2);
});

it('keeps a single default zone', function () {
    $first = ShippingZone::factory()->default()->flatRate()->create();

    $this->actingAs($this->admin)->post(route('admin.shipping.store'), zonePayload(['name' => 'New default', 'is_default' => true, 'cities' => []]));

    expect($first->fresh()->is_default)->toBeFalse()
        ->and(ShippingZone::where('is_default', true)->count())->toBe(1)
        ->and(ShippingZone::where('is_default', true)->value('name'))->toBe('New default');
});

it('deletes a zone with its cities and rates', function () {
    $zone = ShippingZone::factory()->withCities(['Fès'])->flatRate()->create();

    $this->actingAs($this->admin)->delete(route('admin.shipping.destroy', $zone))->assertRedirect(route('admin.shipping.index'));

    $this->assertModelMissing($zone);
    expect(DB::table('shipping_zone_cities')->count())->toBe(0)
        ->and(DB::table('shipping_rates')->count())->toBe(0);
});

it('lists the zones and exposes one to the edit form with prices in dirhams', function () {
    $zone = ShippingZone::factory()->default()->withCities(['Fès'])->withRates([[0, 5000, '25.50']])->create(['name' => 'Rest']);

    $this->actingAs($this->admin)->get(route('admin.shipping.index'))
        ->assertInertia(fn (Assert $page) => $page->component('Shipping/Index')->where('zones.0.name', 'Rest')->where('zones.0.cities_count', 1));

    $this->actingAs($this->admin)->get(route('admin.shipping.edit', $zone))
        ->assertInertia(fn (Assert $page) => $page
            ->component('Shipping/Form')
            ->where('zone.cities', ['Fès'])
            ->where('zone.rates.0.price_ht', 25.5)
            ->where('zone.rates.0.max_weight_g', 5000));
});
