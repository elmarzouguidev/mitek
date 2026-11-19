<?php

use App\Models\User\User;
use App\Settings\GeneralSettings;
use App\Settings\PaymentSettings;
use App\Settings\ShippingSettings;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->admin = User::factory()->admin()->create();
});

it('exposes the default settings from the content brief', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.settings.edit'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('Settings/Edit')
            ->where('general.vat_rate', 20)
            ->where('general.email', 'contact@mitek.ma')
            ->where('payment.cod_limit', 2000)
            ->where('payment.bank_name', 'Banque Populaire')
            ->where('shipping.dispatch_days', 2));
});

it('updates the general settings', function () {
    $this->actingAs($this->admin)
        ->put(route('admin.settings.general'), [
            'site_name' => 'Mitek.ma',
            'legal_name' => 'STE MITEK SARL AU',
            'email' => 'contact@mitek.ma',
            'phone' => '0522 11 22 33',
            'whatsapp' => '212600000000',
            'address' => 'Casablanca, Maroc',
            'maps_embed_url' => 'https://www.google.com/maps/embed?pb=test',
            'vat_rate' => 20,
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    expect(app(GeneralSettings::class))
        ->phone->toBe('0522 11 22 33')
        ->whatsapp->toBe('212600000000');
});

it('converts the cash on delivery limit from dirhams to centimes', function () {
    $this->actingAs($this->admin)
        ->put(route('admin.settings.payment'), [
            'bank_name' => 'Banque Populaire',
            'account_holder' => 'STE MITEK SARL AU',
            'rib' => null,
            'cod_limit' => '2500.50',
            'cheque_payee' => 'STE MITEK SARL AU',
            'bank_transfer_delay' => '48 à 72 heures',
            'cash_deposit_delay' => 'environ 24 heures',
            'cheque_delay' => '8 à 20 jours',
        ])
        ->assertSessionHasNoErrors();

    expect(app(PaymentSettings::class)->cod_limit_cents)->toBe(250050);
});

it('converts the own carrier fee to centimes', function () {
    $this->actingAs($this->admin)
        ->put(route('admin.settings.shipping'), [
            'dispatch_days' => 3,
            'delivery_estimate' => '24 à 72 h',
            'own_carrier_fee' => 50,
        ])
        ->assertSessionHasNoErrors();

    expect(app(ShippingSettings::class))
        ->dispatch_days->toBe(3)
        ->own_carrier_fee_cents->toBe(5000);
});

it('validates settings input', function () {
    $this->actingAs($this->admin)
        ->put(route('admin.settings.general'), [
            'site_name' => '',
            'email' => 'not-an-email',
            'whatsapp' => '+212 6',
            'maps_embed_url' => 'http://insecure.example',
            'vat_rate' => 150,
        ])
        ->assertSessionHasErrors(['site_name', 'legal_name', 'email', 'whatsapp', 'maps_embed_url', 'vat_rate', 'address']);
});

it('forbids customers from changing settings', function () {
    $this->actingAs(User::factory()->customer()->create())
        ->put(route('admin.settings.payment'), ['cod_limit' => 999999])
        ->assertForbidden();

    expect(app(PaymentSettings::class)->cod_limit_cents)->toBe(200000);
});
