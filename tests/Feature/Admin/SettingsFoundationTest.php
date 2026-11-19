<?php

use App\Models\Core\Shipping\ShippingZone;
use App\Models\User\User;
use App\Settings\AnnouncementSettings;
use App\Settings\CatalogSettings;
use App\Settings\ClaimsSettings;
use App\Settings\GeneralSettings;
use App\Settings\ShippingSettings;
use Carbon\CarbonImmutable;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->admin = User::factory()->admin()->create();
});

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function announcementPayload(array $overrides = []): array
{
    return array_merge([
        'is_active' => true, 'dismissible' => true, 'text' => 'Livraison offerte ce week-end',
        'link_url' => '/services', 'link_label' => 'Découvrir', 'starts_at' => null, 'ends_at' => null,
    ], $overrides);
}

function claimsPayload(array $overrides = []): array
{
    return array_merge(collect(array_keys(ClaimsSettings::descriptions()))->mapWithKeys(fn ($claim) => [$claim => false])->all(), $overrides);
}

function shippingPayload(array $overrides = []): array
{
    return array_merge(['dispatch_days' => 2, 'delivery_estimate' => '24 à 72 h', 'own_carrier_fee' => 0, 'working_days' => [1, 2, 3, 4, 5, 6], 'holidays' => []], $overrides);
}

it('keeps the new settings screens away from guests and customers', function () {
    $customer = User::factory()->customer()->create();

    foreach (['settings.announcement', 'settings.claims', 'settings.catalog'] as $route) {
        $this->put(route('admin.'.$route))->assertRedirect(route('login'));
        $this->actingAs($customer)->put(route('admin.'.$route))->assertForbidden();
        auth()->logout();
    }
});

describe('defaults', function () {
    it('turns every unconfirmed claim off', function () {
        $claims = app(ClaimsSettings::class);

        foreach (array_keys(ClaimsSettings::descriptions()) as $claim) {
            expect($claims->$claim)->toBeFalse("{$claim} must be off by default");
        }

        expect(array_keys(ClaimsSettings::descriptions()))->toBe(['quote_within_24h', 'dedicated_contact', 'admin_documents_on_request', 'proforma_available', 'pickup_casablanca', 'show_warranty_lines']);
    });

    it('starts without optional contact values so the site hides them', function () {
        $general = app(GeneralSettings::class);

        foreach (['opening_hours', 'whatsapp_hours', 'facebook_url', 'instagram_url', 'linkedin_url', 'youtube_url', 'tiktok_url', 'cndp_number', 'warranty_text'] as $setting) {
            expect($general->$setting)->toBeNull();
        }
    });

    it('starts with a Monday to Saturday calendar, no holidays, no announcement and manual best sellers only', function () {
        expect(app(ShippingSettings::class))->working_days->toBe([1, 2, 3, 4, 5, 6])->holidays->toBe([])
            ->and(app(AnnouncementSettings::class))->is_active->toBeFalse()->text->toBeNull()
            ->and(app(CatalogSettings::class)->best_seller_min_sales)->toBe(0);
    });

    it('gives shipping zones a default transit of one to three working days', function () {
        $zone = ShippingZone::factory()->create();

        expect($zone->transit_min_days)->toBe(1)->and($zone->transit_max_days)->toBe(3);
    });

    it('exposes every group to the settings page', function () {
        $this->actingAs($this->admin)->get(route('admin.settings.edit'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('Settings/Edit')
                ->where('announcement.is_active', false)
                ->where('claims.values.quote_within_24h', false)
                ->has('claims.descriptions', 6)
                ->where('catalog.best_seller_min_sales', 0)
                ->where('shipping.working_days', [1, 2, 3, 4, 5, 6])
                ->where('general.opening_hours', null));
    });
});

describe('contact details and socials', function () {
    it('stores hours, social links, CNDP number and warranty text', function () {
        $this->actingAs($this->admin)->put(route('admin.settings.general'), generalSettingsPayload([
            'opening_hours' => 'Lun–Sam, 9h–18h', 'whatsapp_hours' => 'Lun–Sam, 9h–19h',
            'facebook_url' => 'https://facebook.com/mitek', 'instagram_url' => 'https://instagram.com/mitek',
            'linkedin_url' => 'https://linkedin.com/company/mitek', 'youtube_url' => 'https://youtube.com/@mitek', 'tiktok_url' => 'https://tiktok.com/@mitek',
            'cndp_number' => 'D-123/2026', 'warranty_text' => 'Garantie constructeur',
        ]))->assertSessionHasNoErrors();

        expect(app(GeneralSettings::class))->opening_hours->toBe('Lun–Sam, 9h–18h')->facebook_url->toBe('https://facebook.com/mitek')
            ->cndp_number->toBe('D-123/2026')->warranty_text->toBe('Garantie constructeur');
    });

    it('only accepts https social links', function (string $field, string $value) {
        $this->actingAs($this->admin)->put(route('admin.settings.general'), generalSettingsPayload([$field => $value]))->assertSessionHasErrors($field);
    })->with([
        'http' => ['facebook_url', 'http://facebook.com/mitek'],
        'javascript' => ['instagram_url', 'javascript:alert(1)'],
        'not a url' => ['linkedin_url', 'mitek'],
        'data' => ['youtube_url', 'data:text/html,<script>alert(1)</script>'],
    ]);
});

describe('announcement bar', function () {
    it('saves the bar', function () {
        $this->actingAs($this->admin)->put(route('admin.settings.announcement'), announcementPayload(['starts_at' => '2026-12-01T08:00', 'ends_at' => '2026-12-31T23:59']))
            ->assertSessionHasNoErrors();

        expect(app(AnnouncementSettings::class))->is_active->toBeTrue()->text->toBe('Livraison offerte ce week-end')->link_url->toBe('/services')->starts_at->toBe('2026-12-01T08:00');
    });

    it('is visible only while active, with text, and inside its period', function () {
        $bar = app(AnnouncementSettings::class);
        $bar->is_active = true;
        $bar->text = 'Soldes';
        $bar->starts_at = '2026-12-01T08:00';
        $bar->ends_at = '2026-12-31T23:59';

        $at = fn (string $moment) => $bar->isVisibleAt(CarbonImmutable::parse($moment, config('app.timezone')));

        expect($at('2026-11-30 23:59'))->toBeFalse()
            ->and($at('2026-12-01 08:00'))->toBeTrue()
            ->and($at('2026-12-15 12:00'))->toBeTrue()
            ->and($at('2027-01-01 00:00'))->toBeFalse();

        $bar->ends_at = null;
        expect($at('2030-01-01 00:00'))->toBeTrue();

        $bar->starts_at = null;
        expect($at('2020-01-01 00:00'))->toBeTrue();

        $bar->is_active = false;
        expect($at('2026-12-15 12:00'))->toBeFalse();

        $bar->is_active = true;
        $bar->text = '   ';
        expect($at('2026-12-15 12:00'))->toBeFalse();
    });

    it('uses the application timezone for its dates', function () {
        $bar = app(AnnouncementSettings::class);
        $bar->is_active = true;
        $bar->text = 'Soldes';
        $bar->starts_at = '2026-12-01T00:00';

        expect(config('app.timezone'))->toBe('Africa/Casablanca')
            ->and($bar->isVisibleAt(CarbonImmutable::parse('2026-12-01 00:30', 'Africa/Casablanca')))->toBeTrue()
            ->and($bar->isVisibleAt(CarbonImmutable::parse('2026-11-30 23:30', 'Africa/Casablanca')))->toBeFalse();
    });

    it('requires a text when active and a label with a link', function () {
        $put = fn (array $data) => $this->actingAs($this->admin)->put(route('admin.settings.announcement'), announcementPayload($data));

        $put(['text' => ''])->assertSessionHasErrors('text');
        $put(['is_active' => false, 'text' => ''])->assertSessionHasNoErrors();
        $put(['link_url' => 'https://example.com', 'link_label' => ''])->assertSessionHasErrors('link_label');
        $put(['text' => str_repeat('a', 201)])->assertSessionHasErrors('text');
    });

    it('accepts only https links or paths of the site', function (string $link, bool $valid) {
        $response = $this->actingAs($this->admin)->put(route('admin.settings.announcement'), announcementPayload(['link_url' => $link]));

        $valid ? $response->assertSessionHasNoErrors() : $response->assertSessionHasErrors('link_url');
    })->with([
        'internal path' => ['/bons-plans', true],
        'https' => ['https://example.com/promo', true],
        'http' => ['http://example.com', false],
        'javascript' => ['javascript:alert(1)', false],
        'data' => ['data:text/html,x', false],
        'protocol relative' => ['//evil.example', false],
        'relative without slash' => ['promo', false],
    ]);

    it('validates the dates', function () {
        $put = fn (array $data) => $this->actingAs($this->admin)->put(route('admin.settings.announcement'), announcementPayload($data));

        $put(['starts_at' => 'tomorrow'])->assertSessionHasErrors('starts_at');
        $put(['starts_at' => '2026-12-10T10:00', 'ends_at' => '2026-12-01T10:00'])->assertSessionHasErrors('ends_at');
    });
});

describe('claims', function () {
    it('lets the admin confirm a claim and switch it off again', function () {
        $this->actingAs($this->admin)->put(route('admin.settings.claims'), claimsPayload(['quote_within_24h' => true, 'pickup_casablanca' => true]))->assertSessionHasNoErrors();

        expect(app(ClaimsSettings::class))->quote_within_24h->toBeTrue()->pickup_casablanca->toBeTrue()->dedicated_contact->toBeFalse();

        $this->actingAs($this->admin)->put(route('admin.settings.claims'), claimsPayload())->assertSessionHasNoErrors();

        expect(app(ClaimsSettings::class)->quote_within_24h)->toBeFalse();
    });

    it('requires every claim to be sent explicitly', function () {
        $this->actingAs($this->admin)->put(route('admin.settings.claims'), ['quote_within_24h' => true])->assertSessionHasErrors(['dedicated_contact', 'proforma_available']);

        expect(app(ClaimsSettings::class)->quote_within_24h)->toBeFalse();
    });

    it('ignores unknown claims', function () {
        $this->actingAs($this->admin)->put(route('admin.settings.claims'), claimsPayload(['free_unicorns' => true]))->assertSessionHasNoErrors();

        expect(isset(app(ClaimsSettings::class)->free_unicorns))->toBeFalse();
    });
});

describe('delivery calendar', function () {
    it('stores the working days sorted and the holidays sorted', function () {
        $this->actingAs($this->admin)->put(route('admin.settings.shipping'), shippingPayload(['working_days' => ['5', '1', '3'], 'holidays' => ['2026-12-25', '2026-11-18']]))
            ->assertSessionHasNoErrors();

        expect(app(ShippingSettings::class))->working_days->toBe([1, 3, 5])->holidays->toBe(['2026-11-18', '2026-12-25']);
    });

    it('accepts an empty holiday list', function () {
        $this->actingAs($this->admin)->put(route('admin.settings.shipping'), shippingPayload(['holidays' => null]))->assertSessionHasNoErrors();

        expect(app(ShippingSettings::class)->holidays)->toBe([]);
    });

    it('validates the calendar', function (array $overrides, string $field) {
        $this->actingAs($this->admin)->put(route('admin.settings.shipping'), shippingPayload($overrides))->assertSessionHasErrors($field);
    })->with([
        'no working day' => [['working_days' => []], 'working_days'],
        'unknown weekday' => [['working_days' => [0]], 'working_days.0'],
        'weekday 8' => [['working_days' => [8]], 'working_days.0'],
        'duplicate weekday' => [['working_days' => [1, 1]], 'working_days.0'],
        'bad holiday format' => [['holidays' => ['25/12/2026']], 'holidays.0'],
        'impossible date' => [['holidays' => ['2026-02-30']], 'holidays.0'],
        'duplicate holiday' => [['holidays' => ['2026-12-25', '2026-12-25']], 'holidays.0'],
    ]);
});

describe('catalogue', function () {
    it('saves the best-seller threshold', function () {
        $this->actingAs($this->admin)->put(route('admin.settings.catalog'), ['best_seller_min_sales' => 25])->assertSessionHasNoErrors();

        expect(app(CatalogSettings::class)->best_seller_min_sales)->toBe(25);
    });

    it('validates the threshold', function (mixed $value) {
        $this->actingAs($this->admin)->put(route('admin.settings.catalog'), ['best_seller_min_sales' => $value])->assertSessionHasErrors('best_seller_min_sales');
    })->with(['negative' => -1, 'text' => 'many', 'empty' => '', 'huge' => 1000000]);
});
