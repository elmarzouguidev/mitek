<?php

use App\Models\Core\Product\Category;
use App\Models\Core\Product\Product;
use App\Settings\AnnouncementSettings;
use App\Settings\GeneralSettings;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

function announce(array $values): void
{
    $settings = app(AnnouncementSettings::class);
    foreach (array_merge(['is_active' => true, 'text' => 'Livraison offerte ce mois', 'link_url' => null, 'link_label' => null, 'starts_at' => null, 'ends_at' => null, 'dismissible' => true], $values) as $key => $value) {
        $settings->{$key} = $value;
    }
    $settings->save();
}

it('hides links to features whose route does not exist yet', function () {
    expect(nav_feature('pro'))->toBeFalse()->and(nav_feature('quote'))->toBeFalse()->and(nav_feature('callback'))->toBeTrue();

    $this->get(route('home'))
        ->assertOk()
        ->assertDontSee('Espace pro');
});

it('shows a feature link as soon as its route exists', function () {
    Route::get('/espace-pro-test', fn () => 'ok')->name('pro.show');
    Route::getRoutes()->refreshNameLookups();

    expect(nav_feature('pro'))->toBeTrue();
    $this->get(route('home'))->assertSee('Espace pro');
});

it('has no language switcher', function () {
    $this->get(route('home'))->assertDontSee('Français')->assertDontSee('العربية')->assertDontSee('?lang=', false)->assertDontSee('hreflang', false);
});

it('shows the announcement only while it is visible', function () {
    announce([]);
    $this->get(route('home'))->assertSee('Livraison offerte ce mois')->assertSee('data-announcement', false);

    announce(['ends_at' => now()->subDay()->format('Y-m-d\TH:i')]);
    $this->get(route('home'))->assertDontSee('Livraison offerte ce mois');

    announce(['starts_at' => now()->addDay()->format('Y-m-d\TH:i')]);
    $this->get(route('home'))->assertDontSee('Livraison offerte ce mois');

    announce(['is_active' => false]);
    $this->get(route('home'))->assertDontSee('Livraison offerte ce mois');
});

it('keys the dismissal on the text and link, and prints the pre-paint script', function () {
    announce(['link_url' => 'https://mitek.ma/promo', 'link_label' => 'Voir']);
    $hash = substr(sha1('Livraison offerte ce mois|https://mitek.ma/promo|Voir'), 0, 10);

    $this->get(route('home'))
        ->assertSee("mitek_announce_{$hash}", false)
        ->assertSee('announce-dismissed', false);

    announce(['link_url' => 'https://mitek.ma/promo', 'link_label' => 'Voir', 'text' => 'Autre texte']);
    $this->get(route('home'))->assertDontSee("mitek_announce_{$hash}", false);
});

it('prints no dismissal script when the bar is not dismissible or hidden', function () {
    announce(['dismissible' => false]);
    $this->get(route('home'))->assertSee('Livraison offerte ce mois')->assertDontSee('announce-dismissed', false);

    announce(['is_active' => false]);
    $this->get(route('home'))->assertDontSee('announce-dismissed', false);
});

it('shows the WhatsApp link and email in the top bar only with data', function () {
    $this->get(route('home'))->assertDontSee('wa.me');

    $site = app(GeneralSettings::class);
    $site->whatsapp = '212600000000';
    $site->save();

    $this->get(route('home'))->assertSee('https://wa.me/212600000000', false)->assertSee($site->email);
});

it('renders the mega menu as server-side links with counts and ARIA state', function () {
    $family = Category::factory()->create(['name' => 'Informatique générale', 'short_name' => 'Informatique']);
    $child = Category::factory()->childOf($family)->create(['name' => 'Claviers']);
    Product::factory()->count(2)->create(['category_id' => $child->id]);

    $html = $this->get(route('home'))->assertOk()->getContent();

    expect($html)->toContain(route('categories.show', $family->slug))
        ->toContain(route('categories.show', $child->slug))
        ->toContain('Claviers')
        ->toContain('aria-controls="mega-menu"')
        ->toContain(':aria-expanded="open')
        ->toContain('>Informatique<');
});

it('shows the featured block only for active featured products of the family', function () {
    $family = Category::factory()->create();
    Product::factory()->featured()->inactive()->create(['category_id' => $family->id, 'name' => 'Produit vedette caché']);

    $this->get(route('home'))->assertDontSee('À la une')->assertDontSee('Produit vedette caché');

    Product::factory()->featured()->create(['category_id' => $family->id, 'name' => 'Produit vedette visible']);

    $this->get(route('home'))->assertSee('À la une')->assertSee('Produit vedette visible')->assertDontSee('Produit vedette caché');
});

it('builds the menu with a single cache entry and rebuilds it when a product or category is saved', function () {
    $family = Category::factory()->create(['name' => 'Famille Alpha']);
    $this->get(route('home'));
    expect(Cache::has(Category::MENU_CACHE_KEY))->toBeTrue();

    $product = Product::factory()->create(['category_id' => $family->id]);
    expect(Cache::has(Category::MENU_CACHE_KEY))->toBeFalse();

    $this->get(route('home'));
    expect(Cache::has(Category::MENU_CACHE_KEY))->toBeTrue();

    $product->update(['name' => 'Renommé']);
    expect(Cache::has(Category::MENU_CACHE_KEY))->toBeFalse();

    $this->get(route('home'));
    $family->update(['name' => 'Famille Beta']);
    expect(Cache::has(Category::MENU_CACHE_KEY))->toBeFalse();
});

it('falls back to the full name when a category has no short name', function () {
    Category::factory()->create(['name' => 'Mobilier de bureau', 'short_name' => null]);

    $this->get(route('home'))->assertSee('>Mobilier de bureau<', false);
});

it('does not add queries when the menu grows', function () {
    $count = function () {
        DB::flushQueryLog();
        DB::enableQueryLog();
        $this->get(route('home'))->assertOk();
        $total = count(DB::getQueryLog());
        DB::disableQueryLog();

        return $total;
    };

    $family = Category::factory()->create();
    Product::factory()->featured()->create(['category_id' => $family->id]);
    $count();
    $small = $count();

    foreach (range(1, 4) as $i) {
        $other = Category::factory()->create();
        Category::factory()->childOf($other)->create();
        Product::factory()->featured()->create(['category_id' => $other->id]);
    }
    $count();

    expect($count())->toBe($small);
});

it('makes only the main header sticky and exposes the mobile drawer as a dialog', function () {
    $this->get(route('home'))
        ->assertSee('sticky top-0 z-40', false)
        ->assertSee('role="dialog"', false)
        ->assertSee('aria-modal="true"', false)
        ->assertSee('aria-controls="mobile-drawer"', false);
});

it('renders the search box as an ARIA combobox that submits without JavaScript', function () {
    $this->get(route('home'))
        ->assertSee('role="combobox"', false)
        ->assertSee('aria-autocomplete="list"', false)
        ->assertSee('role="listbox"', false)
        ->assertSee('action="'.route('search').'"', false)
        ->assertSee('role="search"', false);
});
