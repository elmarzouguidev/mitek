<?php

use App\Models\Core\Product\Category;
use App\Services\Core\Product\CatalogMenu;
use Illuminate\Support\Facades\Route;

beforeEach(function () {
    Category::factory()->create(['name' => 'Toners & Consommables']);
    Category::factory()->create(['name' => 'Mobilier de bureau']);
});

it('shows a branded 404 page with a search form and the main categories', function () {
    $response = $this->get('/cette-page-nexiste-pas')->assertNotFound();

    $response->assertSee('Page introuvable')
        ->assertSee('Erreur 404')
        ->assertSee('role="search"', false)
        ->assertSee('action="'.url('/recherche').'"', false)
        ->assertSee('Toners &amp; Consommables', false)
        ->assertSee('Mobilier de bureau')
        ->assertSee('Retour à l\'accueil')
        ->assertSee('<meta name="robots" content="noindex,nofollow">', false);

    assertHeadingOutline($response->getContent());
});

it('returns the 404 page for unknown products, categories, services and pages', function () {
    foreach (['/produit/inconnu', '/categories/inconnue', '/services/inconnu', '/commande/00000000-0000-0000-0000-000000000000', '/devis/00000000-0000-0000-0000-000000000000'] as $url) {
        $this->get($url)->assertNotFound()->assertSee('Page introuvable');
    }

});

it('shows a branded 500 page with a search form and the main categories', function () {
    config(['app.debug' => false]);
    Route::get('/__boom', fn () => throw new RuntimeException('boom'));

    $response = $this->get('/__boom')->assertStatus(500);

    $response->assertSee('Un problème est survenu')
        ->assertSee('Erreur 500')
        ->assertSee('role="search"', false)
        ->assertSee('Mobilier de bureau')
        ->assertDontSee('boom');

    assertHeadingOutline($response->getContent());
});

it('keeps rendering the 500 page when the menu cannot be loaded', function () {
    config(['app.debug' => false]);
    $this->mock(CatalogMenu::class, fn ($mock) => $mock->shouldReceive('tree')->andThrow(new RuntimeException('database is down')));
    Route::get('/__boom', fn () => throw new RuntimeException('boom'));

    $this->get('/__boom')->assertStatus(500)
        ->assertSee('Un problème est survenu')
        ->assertSee('role="search"', false)
        ->assertSee('Retour à l\'accueil')
        ->assertDontSee('Nos catégories');
});

it('does not read the settings, so a database failure cannot break the error pages', function () {
    $source = file_get_contents(resource_path('views/components/layouts/error.blade.php'));

    expect($source)->not->toContain('Settings')->not->toContain('$site');
});
