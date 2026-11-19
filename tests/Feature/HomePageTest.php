<?php

use App\Settings\GeneralSettings;

it('renders the French home page with the content from the brief', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertSee('Simplifiez votre quotidien professionnel avec Mitek.ma.')
        ->assertSee('Consommables & Toners')
        ->assertSee('Service client disponible 6j/7')
        ->assertSee('lang="fr"', false)
        ->assertSee('<link rel="canonical"', false);
});

it('reads contact details from the settings', function () {
    $settings = app(GeneralSettings::class);
    $settings->phone = '0522 00 00 00';
    $settings->whatsapp = '212600000000';
    $settings->save();

    $this->get(route('home'))
        ->assertSee('contact@mitek.ma')
        ->assertSee('0522 00 00 00')
        ->assertSee('https://wa.me/212600000000', false);
});

it('hides the phone and WhatsApp button while they are not configured', function () {
    $this->get(route('home'))
        ->assertDontSee('wa.me')
        ->assertDontSee('tel:', false);
});
