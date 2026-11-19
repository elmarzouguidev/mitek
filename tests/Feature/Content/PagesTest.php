<?php

use App\Enums\PageKey;
use App\Models\Core\Content\Page;
use App\Models\User\User;
use App\Services\Content\ContentRenderer;
use App\Settings\GeneralSettings;
use App\Settings\PaymentSettings;
use App\Settings\ShippingSettings;
use App\Support\Money;
use Database\Seeders\FaqSeeder;
use Database\Seeders\PageSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed(PageSeeder::class);
});

describe('storefront', function () {
    it('serves every CMS page at its fixed French URL with a single h1', function (PageKey $key, string $url) {
        $response = $this->get($url)->assertOk()->assertSee($key->label());

        assertHeadingOutline($response->getContent());
        expect(route('pages.'.$key->value, absolute: false))->toBe($url);
    })->with([
        'about' => [PageKey::About, '/a-propos'],
        'why mitek' => [PageKey::WhyMitek, '/pourquoi-mitek'],
        'payment' => [PageKey::Payment, '/modes-de-paiement'],
        'delivery' => [PageKey::Delivery, '/modes-de-livraison'],
        'legal notice' => [PageKey::LegalNotice, '/mentions-legales'],
        'terms' => [PageKey::Terms, '/cgv'],
        'privacy' => [PageKey::Privacy, '/politique-de-confidentialite'],
    ]);

    it('hides an unpublished page', function () {
        Page::where('key', PageKey::About)->update(['is_published' => false]);

        $this->get('/a-propos')->assertNotFound();
        $this->get('/cgv')->assertOk();
    });

    it('seeds the about and why-Mitek content from Mitek.md', function () {
        $this->get('/a-propos')->assertSee('entreprise marocaine spécialisée dans la distribution de matériel de bureau')->assertSee('Fiabilité')->assertSee('Proximité')->assertSee('Innovation');
        $this->get('/pourquoi-mitek')->assertSee('Une équipe technique certifiée')->assertSee('Un service client')->assertSee('6j/7');
    });

    it('offers next steps on the marketing pages only', function () {
        $this->get('/a-propos')->assertSee('Voir nos services')->assertSee(route('services.index'), false);
        $this->get('/pourquoi-mitek')->assertSee('Nous contacter');
        $this->get('/cgv')->assertDontSee('Voir nos services');
    });

    it('links the CMS pages from the footer of every page', function () {
        $this->get(route('home'))
            ->assertSee(route('pages.payment'), false)
            ->assertSee(route('pages.delivery'), false)
            ->assertSee(route('pages.legal-notice'), false)
            ->assertSee(route('pages.terms'), false)
            ->assertSee(route('pages.privacy'), false)
            ->assertSee(route('faq'), false);
    });
});

describe('settings driven content', function () {
    it('takes the cash on delivery limit, the delays and the bank holder from the settings', function () {
        $this->get('/modes-de-paiement')
            ->assertSee('jusqu\'à '.Money::fromCents(200000)->format().' TTC', false)
            ->assertSee('STE MITEK SARL AU')
            ->assertSee('Banque Populaire')
            ->assertSee('48 à 72 heures')
            ->assertSee('8 à 20 jours');

        $payment = app(PaymentSettings::class);
        $payment->cod_limit_cents = 350000;
        $payment->account_holder = 'NOUVEAU TITULAIRE SARL';
        $payment->bank_transfer_delay = '24 heures';
        $payment->save();

        $this->get('/modes-de-paiement')
            ->assertSee(Money::fromCents(350000)->format())
            ->assertSee('NOUVEAU TITULAIRE SARL')
            ->assertSee('24 heures')
            ->assertDontSee(Money::fromCents(200000)->format());
    });

    it('takes the shipping delays from the settings', function () {
        $shipping = app(ShippingSettings::class);
        $shipping->dispatch_days = 5;
        $shipping->delivery_estimate = '48 à 96 h selon la ville';
        $shipping->save();

        $this->get('/modes-de-livraison')->assertSee('dans les 5 jours')->assertSee('48 à 96 h selon la ville');
    });

    it('prints a visible placeholder for an empty setting instead of hiding it', function () {
        $this->get('/modes-de-paiement')->assertSee('RIB : [À COMPLÉTER]');
        $this->get('/mentions-legales')->assertSee('RC : [À COMPLÉTER]')->assertSee('ICE : [À COMPLÉTER]')->assertSee('CNSS : [À COMPLÉTER]');

        $settings = app(GeneralSettings::class);
        $settings->rc_number = 'RC-12345';
        $settings->save();

        $this->get('/mentions-legales')->assertSee('RC : RC-12345');
    });

    it('lists legal text that is missing as visible TODO placeholders', function () {
        $this->get('/mentions-legales')->assertSee('[À COMPLÉTER]')->assertSee('Hébergement');
        $this->get('/cgv')->assertSee('[À COMPLÉTER : droit de rétractation');
        $this->get('/politique-de-confidentialite')->assertSee('[À COMPLÉTER : numéro de la déclaration');
    });

    it('mentions law 09-08 and the CNDP on the privacy page', function () {
        $this->get('/politique-de-confidentialite')
            ->assertSee('loi n° 09-08')
            ->assertSee('CNDP')
            ->assertSee("droit d'accès, de rectification et d'opposition", false);
    });

    it('shows the FAQ of its category under the payment and delivery pages', function () {
        $this->seed(FaqSeeder::class);

        $this->get('/modes-de-paiement')->assertSee('Puis-je payer à la livraison ?')->assertSee('Quels modes de paiement acceptez-vous ?')->assertDontSee('Quels sont les délais de livraison ?');
        $this->get('/modes-de-livraison')->assertSee('Quels sont les délais de livraison ?')->assertDontSee('Puis-je payer à la livraison ?');
        $this->get('/a-propos')->assertDontSee('FAQPage', false);
    });
});

describe('renderer', function () {
    it('fills tokens, tolerates spaces and leaves unknown tokens untouched', function () {
        $renderer = app(ContentRenderer::class);

        expect($renderer->replaceTokens('{{vat_rate}} % / {{ vat_rate }} % / {{nope}}'))->toBe('20 % / 20 % / {{nope}}')
            ->and($renderer->replaceTokens('{{phone}}'))->toBe('[À COMPLÉTER]')
            ->and($renderer->replaceTokens('{{cod_limit}}'))->toBe(Money::fromCents(200000)->format())
            ->and($renderer->replaceTokens('{{own_carrier_fee}}'))->toBe('[À COMPLÉTER]');
    });

    it('renders Markdown and strips raw HTML and unsafe links', function () {
        $html = app(ContentRenderer::class)->html("## Titre\n\n**gras** <script>alert(1)</script><img src=x onerror=alert(1)> [x](javascript:alert(1)) [ok](https://example.com)")->toHtml();

        expect($html)->toContain('<h2>Titre</h2>')->toContain('<strong>gras</strong>')->toContain('https://example.com')
            ->not->toContain('<script')->not->toContain('onerror')->not->toContain('javascript:');
    });

    it('does not let a setting value inject HTML', function () {
        $settings = app(GeneralSettings::class);
        $settings->share_capital = '<script>alert(1)</script>';
        $settings->save();

        expect(app(ContentRenderer::class)->html('Capital : {{share_capital}}')->toHtml())->not->toContain('<script');
    });

    it('produces plain text for meta tags', function () {
        expect(app(ContentRenderer::class)->text("## Titre\n\nUn **texte** ici."))->toBe('Titre Un texte ici.');
    });
});

describe('admin', function () {
    beforeEach(fn () => $this->admin = User::factory()->admin()->create());

    it('keeps the page screens away from guests and customers', function () {
        $page = Page::firstOrFail();
        $customer = User::factory()->customer()->create();

        foreach ([['get', route('admin.pages.index')], ['get', route('admin.pages.edit', $page)], ['put', route('admin.pages.update', $page)]] as [$method, $url]) {
            $this->$method($url)->assertRedirect(route('login'));
            $this->actingAs($customer)->$method($url)->assertForbidden();
            auth()->logout();
        }
    });

    it('lists the seven pages with their public URL and publication state', function () {
        Page::where('key', PageKey::Terms)->update(['is_published' => false]);

        $this->actingAs($this->admin)->get(route('admin.pages.index'))
            ->assertInertia(fn (Assert $page) => $page->component('Pages/Index')->has('pages', 7)->where('pages.0.url', route('pages.about'))->where('pages.5.is_published', false));
    });

    it('exposes the page and the available tokens with their current values', function () {
        $page = Page::where('key', PageKey::Payment)->firstOrFail();

        $this->actingAs($this->admin)->get(route('admin.pages.edit', $page))
            ->assertInertia(fn (Assert $inertia) => $inertia
                ->component('Pages/Form')
                ->where('page.title', 'Modes de paiement')
                ->where('page.url', route('pages.payment'))
                ->where('tokens', fn ($tokens) => collect($tokens)->contains(fn ($token) => $token['token'] === '{{cod_limit}}' && str_contains($token['value'], 'DH'))));
    });

    it('edits and publishes a page without changing its URL', function () {
        $page = Page::where('key', PageKey::About)->firstOrFail();

        $this->actingAs($this->admin)->put(route('admin.pages.update', $page), [
            'title' => 'Qui sommes-nous', 'content' => "## Notre histoire\n\nTexte.", 'meta_title' => 'SEO about', 'meta_description' => 'Desc about.', 'is_published' => true,
        ])->assertSessionHas('success');

        $this->get('/a-propos')->assertOk()->assertSee('Notre histoire')->assertSee('<title>SEO about · Mitek.ma</title>', false)->assertSee('content="Desc about."', false);
        expect($page->fresh()->slug)->toBe('a-propos');
    });

    it('unpublishes a page', function () {
        $page = Page::where('key', PageKey::Terms)->firstOrFail();

        $this->actingAs($this->admin)->put(route('admin.pages.update', $page), ['title' => $page->title, 'content' => $page->content, 'is_published' => false]);

        $this->get('/cgv')->assertNotFound();
    });

    it('validates the page', function (array $overrides, string $field) {
        $page = Page::firstOrFail();

        $this->actingAs($this->admin)->put(route('admin.pages.update', $page), array_merge(['title' => 'T', 'content' => 'C'], $overrides))->assertSessionHasErrors($field);
    })->with([
        'title' => [['title' => ''], 'title'],
        'content' => [['content' => ''], 'content'],
        'meta description' => [['meta_description' => str_repeat('a', 321)], 'meta_description'],
    ]);

    it('has no route to create or delete a page', function () {
        expect(Route::has('admin.pages.store'))->toBeFalse()->and(Route::has('admin.pages.destroy'))->toBeFalse();
    });
});
