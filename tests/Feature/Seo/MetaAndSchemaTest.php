<?php

use App\Models\Core\Product\Brand;
use App\Models\Core\Product\Category;
use App\Models\Core\Product\Product;
use App\Models\Core\Service\Service;
use App\Models\User\User;
use App\Settings\GeneralSettings;
use App\Support\Seo;
use Database\Seeders\FaqSeeder;
use Database\Seeders\PageSeeder;
use Illuminate\Http\UploadedFile;

/**
 * One representative URL per public page type.
 *
 * @return array<string, string>
 */
function publicUrls(): array
{
    $category = Category::factory()->create(['name' => 'Toners', 'description' => 'Tous nos toners.']);
    $product = Product::factory()->for($category)->priced(790)->create(['name' => 'Toner HP 312A', 'excerpt' => 'Toner original HP.']);
    $service = Service::factory()->create(['title' => 'Maintenance', 'summary' => 'Maintenance du parc.']);

    return [
        'home' => route('home'),
        'category' => route('categories.show', $category->slug),
        'product' => route('products.show', $product->slug),
        'services' => route('services.index'),
        'service' => route('services.show', $service->slug),
        'about' => route('pages.about'),
        'payment' => route('pages.payment'),
        'terms' => route('pages.terms'),
        'faq' => route('faq'),
        'contact' => route('contact'),
    ];
}

beforeEach(function () {
    $this->seed(PageSeeder::class);
    $this->seed(FaqSeeder::class);
});

describe('meta tags', function () {
    it('gives every public page a unique title and a description of reasonable length', function () {
        $titles = [];

        foreach (publicUrls() as $name => $url) {
            $html = $this->get($url)->assertOk()->getContent();

            preg_match('#<title>(.*?)</title>#s', $html, $title);
            preg_match('#<meta name="description" content="(.*?)">#s', $html, $description);

            expect($title[1] ?? '')->not->toBe('', "{$name} has a title");
            expect(mb_strlen(html_entity_decode($description[1] ?? '')))->toBeBetween(10, 160, "{$name} has a sensible description");
            $titles[$name] = $title[1];
        }

        expect(array_unique($titles))->toHaveCount(count($titles));
    });

    it('declares an absolute canonical URL, Open Graph and Twitter tags with a default share image', function () {
        foreach (publicUrls() as $name => $url) {
            $html = $this->get($url)->getContent();

            expect($html)
                ->toContain('<link rel="canonical" href="http')
                ->toContain('<meta property="og:title"')
                ->toContain('<meta property="og:description"')
                ->toContain('<meta property="og:url"')
                ->toContain('<meta property="og:site_name" content="Mitek.ma">')
                ->toContain('<meta property="og:locale" content="fr_MA">')
                ->toContain('<meta name="twitter:card" content="summary_large_image">')
                ->toContain('<meta name="twitter:title"')
                ->toContain(asset('images/share-default.png'));
        }

        expect(public_path('images/share-default.png'))->toBeFile();
        [$width, $height] = getimagesize(public_path('images/share-default.png'));
        expect([$width, $height])->toBe([1200, 630]);
    });

    it('uses the product image as the share image', function () {
        $product = Product::factory()->create();
        $product->addMedia(UploadedFile::fake()->image('item.jpg', 1400, 1400))->toMediaCollection(Product::GALLERY);

        $html = $this->get(route('products.show', $product->slug))->getContent();

        expect($html)->toContain('<meta property="og:image" content="'.$product->getFirstMediaUrl(Product::GALLERY, 'large').'">')
            ->toContain('<meta property="og:type" content="product">');
    });

    it('generates descriptions from the content when no SEO field is set', function () {
        $category = Category::factory()->create(['name' => 'Papeterie', 'description' => 'Stylos, carnets et surligneurs pour le bureau.']);
        $bare = Category::factory()->create(['name' => 'Sans texte', 'description' => null]);

        $this->get(route('categories.show', $category->slug))->assertSee('<meta name="description" content="Stylos, carnets et surligneurs pour le bureau.">', false);
        $this->get(route('categories.show', $bare->slug))->assertSee('Découvrez notre sélection Sans texte', false);
    });

    it('prefers the SEO fields over the generated ones', function () {
        $product = Product::factory()->create(['name' => 'Plain', 'meta_title' => 'Custom title', 'meta_description' => 'Custom description.']);

        $this->get(route('products.show', $product->slug))
            ->assertSee('<title>Custom title · Mitek.ma</title>', false)
            ->assertSee('<meta name="description" content="Custom description.">', false);
    });

    it('cuts generated descriptions at a word boundary and strips markup', function () {
        $long = '**Texte** '.str_repeat('mot ', 80);

        $description = Seo::description($long);

        expect(mb_strlen($description))->toBeLessThanOrEqual(155)
            ->and($description)->toEndWith('…')->not->toContain('**')->not->toContain('mot…mo')
            ->and(Seo::description(null))->toBeNull()
            ->and(Seo::description('   '))->toBeNull()
            ->and(Seo::description('Court & simple'))->toBe('Court & simple');
    });

    it('marks utility and private pages noindex', function (string $route) {
        $this->get(route($route))->assertSee('<meta name="robots" content="noindex', false);
    })->with(['cart.show', 'login', 'register', 'password.request']);

    it('marks search results noindex and public pages indexable', function () {
        $this->get(route('search', ['q' => 'toner']))->assertSee('<meta name="robots" content="noindex,follow">', false);
        $this->get(route('home'))->assertSee('<meta name="robots" content="index,follow">', false);
        $this->get(route('faq'))->assertSee('<meta name="robots" content="index,follow">', false);
    });

    it('marks the checkout and the account noindex', function () {
        $product = Product::factory()->create(['stock_quantity' => 5]);
        enableShipping();

        $this->withSession(cartSession([[$product, 1]]))->get(route('checkout.show'))->assertSee('noindex,nofollow', false);
        $this->actingAs(User::factory()->customer()->create())->get(route('account.index'))->assertSee('noindex,nofollow', false);
    });
});

describe('JSON-LD', function () {
    it('describes the Organization and the LocalBusiness on every public page', function () {
        $settings = app(GeneralSettings::class);
        $settings->phone = '0522 11 22 33';
        $settings->save();

        foreach (publicUrls() as $name => $url) {
            $html = $this->get($url)->getContent();
            [$organization] = jsonLdOfType($html, 'Organization');
            [$business] = jsonLdOfType($html, 'LocalBusiness');

            expect($organization['name'])->toBe('Mitek.ma', $name)
                ->and($organization['legalName'])->toBe('STE MITEK SARL AU')
                ->and($organization['email'])->toBe('contact@mitek.ma')
                ->and($organization['telephone'])->toBe('0522 11 22 33')
                ->and($organization['address']['addressLocality'])->toBe('Casablanca')
                ->and($organization['address']['addressCountry'])->toBe('MA')
                ->and($organization['logo'])->toBe(asset('images/logo-mitek.png'))
                ->and($organization['url'])->toBe(url('/'))
                ->and($business['address']['addressLocality'])->toBe('Casablanca')
                ->and($business['parentOrganization']['@id'])->toBe($organization['@id'])
                ->and($organization['contactPoint']['contactType'])->toBe('customer service');
        }
    });

    it('takes the contact details from the settings and omits what is empty', function () {
        $html = $this->get(route('home'))->getContent();
        [$organization] = jsonLdOfType($html, 'Organization');

        expect($organization)->not->toHaveKey('telephone')
            ->and($organization['contactPoint'])->not->toHaveKey('telephone');

        $settings = app(GeneralSettings::class);
        $settings->city = 'Rabat';
        $settings->registered_address = '12 rue Exemple';
        $settings->save();

        [$organization] = jsonLdOfType($this->get(route('home'))->getContent(), 'Organization');
        expect($organization['address'])->toMatchArray(['addressLocality' => 'Rabat', 'streetAddress' => '12 rue Exemple']);
    });

    it('describes a product with an offer in MAD: price TTC and availability', function () {
        $brand = Brand::factory()->create(['name' => 'HP']);
        $product = Product::factory()->priced('790')->withBrand($brand)->create(['name' => 'Toner HP 312A', 'reference' => 'CF380A', 'excerpt' => 'Toner original.', 'stock_quantity' => 12]);

        [$schema] = jsonLdOfType($this->get(route('products.show', $product->slug))->getContent(), 'Product');

        expect($schema['@context'])->toBe('https://schema.org')
            ->and($schema['name'])->toBe('Toner HP 312A')
            ->and($schema['sku'])->toBe('CF380A')
            ->and($schema['brand'])->toBe(['@type' => 'Brand', 'name' => 'HP'])
            ->and($schema['description'])->toBe('Toner original.')
            ->and($schema['offers']['@type'])->toBe('Offer')
            ->and($schema['offers']['priceCurrency'])->toBe('MAD')
            ->and($schema['offers']['price'])->toBe('948.00')
            ->and($schema['offers']['availability'])->toBe('https://schema.org/InStock')
            ->and($schema['offers']['url'])->toBe(route('products.show', $product->slug));
    });

    it('reports out of stock products as such and uses the product VAT rate', function () {
        $product = Product::factory()->priced('100')->outOfStock()->create(['vat_rate' => 7]);

        [$schema] = jsonLdOfType($this->get(route('products.show', $product->slug))->getContent(), 'Product');

        expect($schema['offers']['availability'])->toBe('https://schema.org/OutOfStock')
            ->and($schema['offers']['price'])->toBe('107.00');
    });

    it('includes the product image when there is one', function () {
        $product = Product::factory()->create();
        $product->addMedia(UploadedFile::fake()->image('item.jpg', 1400, 1400))->toMediaCollection(Product::GALLERY);

        [$schema] = jsonLdOfType($this->get(route('products.show', $product->slug))->getContent(), 'Product');

        expect($schema['image'])->toBe($product->getFirstMediaUrl(Product::GALLERY, 'large'));
    });

    it('cannot be broken out of the script element by product data', function () {
        $product = Product::factory()->create(['name' => 'Evil </script><script>alert(1)</script>', 'excerpt' => '"quotes" & <b>tags</b>']);

        $html = $this->get(route('products.show', $product->slug))->getContent();

        expect($html)->not->toContain('</script><script>alert(1)')
            ->and(fn () => jsonLdNodes($html))->not->toThrow(JsonException::class);
        [$schema] = jsonLdOfType($html, 'Product');
        expect($schema['name'])->toBe('Evil </script><script>alert(1)</script>');
    });

    it('describes breadcrumbs with absolute URLs and consecutive positions', function () {
        $parent = Category::factory()->create(['name' => 'Famille']);
        $child = Category::factory()->childOf($parent)->create(['name' => 'Sous-famille']);
        $product = Product::factory()->for($child)->create(['name' => 'Article']);

        [$crumbs] = jsonLdOfType($this->get(route('products.show', $product->slug))->getContent(), 'BreadcrumbList');
        $items = $crumbs['itemListElement'];

        expect(collect($items)->pluck('position')->all())->toBe([1, 2, 3, 4])
            ->and(collect($items)->pluck('name')->all())->toBe(['Accueil', 'Famille', 'Sous-famille', 'Article'])
            ->and($items[0]['item'])->toBe(route('home'))
            ->and($items[1]['item'])->toBe(route('categories.show', $parent->slug))
            ->and(collect($items)->every(fn ($item) => str_starts_with($item['item'], 'http')))->toBeTrue();
    });

    it('describes breadcrumbs on every page that shows them', function (string $name) {
        $html = $this->get(publicUrls()[$name])->getContent();

        expect(jsonLdOfType($html, 'BreadcrumbList'))->toHaveCount(1);
        [$crumbs] = jsonLdOfType($html, 'BreadcrumbList');
        expect($crumbs['itemListElement'][0]['name'])->toBe('Accueil')
            ->and(collect($crumbs['itemListElement'])->last()['item'])->toStartWith('http');
    })->with(['category', 'product', 'service', 'about', 'payment', 'terms', 'faq', 'contact']);

    it('describes a service', function () {
        $service = Service::factory()->create(['title' => 'Cybersécurité', 'summary' => 'Antivirus et audit.']);

        [$schema] = jsonLdOfType($this->get(route('services.show', $service->slug))->getContent(), 'Service');

        expect($schema['name'])->toBe('Cybersécurité')
            ->and($schema['description'])->toBe('Antivirus et audit.')
            ->and($schema['url'])->toBe(route('services.show', $service->slug))
            ->and($schema['provider']['@id'])->toContain('#organization')
            ->and($schema['areaServed']['name'])->toBe('Maroc');
    });

    it('describes the FAQ on /faq and on the pages that show questions, and nowhere else', function () {
        expect(jsonLdOfType($this->get(route('faq'))->getContent(), 'FAQPage'))->toHaveCount(1)
            ->and(jsonLdOfType($this->get(route('pages.payment'))->getContent(), 'FAQPage'))->toHaveCount(1)
            ->and(jsonLdOfType($this->get(route('pages.delivery'))->getContent(), 'FAQPage'))->toHaveCount(1)
            ->and(jsonLdOfType($this->get(route('pages.about'))->getContent(), 'FAQPage'))->toBe([])
            ->and(jsonLdOfType($this->get(route('home'))->getContent(), 'FAQPage'))->toBe([]);

        $service = Service::factory()->create();
        expect(jsonLdOfType($this->get(route('services.show', $service->slug))->getContent(), 'FAQPage'))->toHaveCount(1);
    });

    it('answers FAQPage questions with the settings values, not the raw tokens', function () {
        [$faq] = jsonLdOfType($this->get(route('pages.payment'))->getContent(), 'FAQPage');
        $answers = collect($faq['mainEntity'])->pluck('acceptedAnswer.text')->implode(' ');

        expect($answers)->toContain('DH')->not->toContain('{{');
    });

    it('does not add product or service schemas where they do not belong', function () {
        $html = $this->get(route('home'))->getContent();

        expect(jsonLdOfType($html, 'Product'))->toBe([])->and(jsonLdOfType($html, 'Service'))->toBe([])->and(jsonLdOfType($html, 'BreadcrumbList'))->toBe([]);
    });
});

describe('structure', function () {
    it('has exactly one h1 and a logical heading order on every public page', function () {
        $category = Category::factory()->create();
        Product::factory()->count(3)->for($category)->create();
        $product = Product::factory()->for($category)->withTiers([5 => 80])->create();
        $product->specifications()->create(['name' => 'Couleur', 'value' => 'Noir', 'position' => 0]);

        $urls = [...publicUrls(), 'search' => route('search', ['q' => 'a']), 'search results' => route('search', ['q' => $product->reference]), 'listing' => route('categories.show', $category->slug), 'product with tiers' => route('products.show', $product->slug), 'cart' => route('cart.show'), 'login' => route('login')];

        foreach ($urls as $name => $url) {
            assertHeadingOutline($this->get($url)->assertOk()->getContent());
        }
    });

    it('gives every image an alt attribute', function () {
        $product = Product::factory()->create(['name' => 'Photographed']);
        $product->addMedia(UploadedFile::fake()->image('a.jpg', 1200, 1200))->toMediaCollection(Product::GALLERY);
        $product->addMedia(UploadedFile::fake()->image('b.jpg', 1200, 1200))->toMediaCollection(Product::GALLERY);
        Product::factory()->for($product->category)->create(); // placeholder image

        $urls = [...publicUrls(), 'photographed' => route('products.show', $product->slug), 'listing' => route('categories.show', $product->category->slug)];

        foreach ($urls as $name => $url) {
            $html = $this->get($url)->getContent();

            preg_match_all('#<img\b[^>]*>#i', $html, $images);
            foreach ($images[0] as $image) {
                expect($image)->toMatch('#\balt="#', "an <img> without alt on {$name}: {$image}");
            }
        }

        $this->get(route('products.show', $product->slug))->assertSee('alt="Photographed"', false);
    });
});
