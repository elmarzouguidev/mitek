<?php

use App\Models\Core\Product\Product;
use App\Settings\GeneralSettings;
use App\Support\Money;
use App\Support\WhatsApp;
use Illuminate\Support\Facades\DB;

function whatsappSettings(?string $number, ?string $hours = null): void
{
    $site = app(GeneralSettings::class);
    $site->whatsapp = $number;
    $site->whatsapp_hours = $hours;
    $site->save();
}

it('builds no link while the number is empty', function () {
    expect(whatsapp_url())->toBeNull()
        ->and(app(WhatsApp::class)->isConfigured())->toBeFalse();
});

it('builds a wa.me link with digits only and an encoded message', function () {
    whatsappSettings('212600000000');

    expect(whatsapp_url())->toBe('https://wa.me/212600000000')
        ->and(whatsapp_url("Bonjour & merci\nà bientôt"))->toBe('https://wa.me/212600000000?text='.rawurlencode("Bonjour & merci\nà bientôt"));
});

it('shows the floating button with the hours from the settings', function () {
    whatsappSettings('212600000000', 'Lun–Sam, 9h–18h');

    $this->get(route('home'))
        ->assertSee('data-whatsapp-float', false)
        ->assertSee('Discuter sur WhatsApp')
        ->assertSee('Horaires : Lun–Sam, 9h–18h')
        ->assertSee('https://wa.me/212600000000', false);
});

it('hides the hours while they are empty and the whole button while the number is empty', function () {
    whatsappSettings('212600000000');
    $this->get(route('home'))->assertSee('data-whatsapp-float', false)->assertDontSee('Horaires :');

    whatsappSettings(null, 'Lun–Sam, 9h–18h');
    $this->get(route('home'))->assertDontSee('data-whatsapp-float', false)->assertDontSee('Discuter sur WhatsApp')->assertDontSee('wa.me');
});

it('prefills the order message with name, reference, TTC price and url', function () {
    whatsappSettings('212600000000');
    $product = Product::factory()->priced(100)->create(['name' => 'Toner « noir » & co <b>', 'reference' => 'TN-1']);

    $message = app(WhatsApp::class)->productMessage($product);

    expect($message)->toContain('Toner « noir » & co <b>')
        ->toContain('Réf. TN-1')
        ->toContain(Money::fromDecimal(120)->format().' TTC')
        ->toContain(route('products.show', $product->slug));

    expect(app(WhatsApp::class)->productLink($product))->toBe('https://wa.me/212600000000?text='.rawurlencode($message));
});

it('adds "Commander par WhatsApp" to product cards only when the number is set', function () {
    $product = Product::factory()->create(['name' => 'Cartouche Alpha']);
    $category = $product->category;

    $this->get(route('categories.show', $category->slug))->assertDontSee('Commander par WhatsApp');

    whatsappSettings('212600000000');
    $html = $this->get(route('categories.show', $category->slug))->assertSee('Commander par WhatsApp')->getContent();

    expect($html)->toContain('https://wa.me/212600000000?text='.rawurlencode(app(WhatsApp::class)->productMessage($product)));
});

it('escapes the message so a hostile product name cannot break out of the link', function () {
    whatsappSettings('212600000000');
    $product = Product::factory()->create(['name' => '"><script>alert(1)</script>']);

    $this->get(route('categories.show', $product->category->slug))->assertDontSee('"><script>alert(1)</script>', false);
    $this->get(route('products.show', $product->slug))->assertDontSee('<script>alert(1)</script>', false);
});

it('shows the WhatsApp call to action on the product page with a quantity-aware link', function () {
    $product = Product::factory()->create();

    $this->get(route('products.show', $product->slug))->assertDontSee('Commander sur WhatsApp');

    whatsappSettings('212600000000');
    $this->get(route('products.show', $product->slug))
        ->assertSee('Commander sur WhatsApp')
        ->assertSee(app(WhatsApp::class)->productLink($product), false)
        ->assertSee('Quantité : :quantity', false);
});

it('does not add queries per card for the WhatsApp links', function () {
    whatsappSettings('212600000000');
    $count = function ($url) {
        DB::flushQueryLog();
        DB::enableQueryLog();
        $this->get($url)->assertOk();
        $total = count(DB::getQueryLog());
        DB::disableQueryLog();

        return $total;
    };

    $first = Product::factory()->withBrand()->withTiers([3 => 5])->create();
    $count(route('categories.show', $first->category->slug));
    $one = $count(route('categories.show', $first->category->slug));

    Product::factory()->count(8)->for($first->category)->withBrand()->withTiers([3 => 5])->create();
    $count(route('categories.show', $first->category->slug));
    $nine = $count(route('categories.show', $first->category->slug));

    expect($nine)->toBe($one);
});
