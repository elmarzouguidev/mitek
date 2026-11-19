<?php

use App\Enums\PageKey;
use App\Jobs\RegenerateSitemap;
use App\Models\Core\Content\FaqItem;
use App\Models\Core\Content\Page;
use App\Models\Core\Product\Category;
use App\Models\Core\Product\Product;
use App\Models\Core\Service\Service;
use App\Services\Seo\SitemapGenerator;
use Database\Seeders\PageSeeder;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Queue;

/**
 * URLs (loc) of a sitemap document.
 *
 * @return array<int, string>
 */
function sitemapUrls(string $xml): array
{
    preg_match_all('#<loc>(.*?)</loc>#', $xml, $matches);

    return array_map('html_entity_decode', $matches[1]);
}

describe('robots.txt', function () {
    it('serves plain text that blocks the private areas and search results', function () {
        $response = $this->get('/robots.txt')->assertOk()->assertHeader('Content-Type', 'text/plain; charset=UTF-8');
        $robots = $response->getContent();

        expect($robots)->toStartWith('User-agent: *')
            ->toContain("Disallow: /admin\n")
            ->toContain("Disallow: /compte\n")
            ->toContain("Disallow: /panier\n")
            ->toContain("Disallow: /commande\n")
            ->toContain("Disallow: /recherche\n")
            ->toContain("Disallow: /devis\n")
            ->toContain('Sitemap: '.route('sitemap'));
    });

    it('does not block public content', function () {
        $robots = $this->get('/robots.txt')->getContent();

        foreach (['/categories', '/produit', '/services', '/faq', '/contact', '/a-propos', '/modes-de-paiement'] as $path) {
            expect($robots)->not->toContain('Disallow: '.$path);
        }
    });

    it('is served by the application, not by a stale static file', function () {
        expect(public_path('robots.txt'))->not->toBeFile();
    });

    it('blocks exactly the prefixes that the private routes live under', function () {
        foreach (config('seo.disallow') as $path) {
            expect($path)->toStartWith('/');
        }

        $privateUrls = [route('account.index', absolute: false), route('cart.show', absolute: false), route('checkout.show', absolute: false), route('search', absolute: false), route('admin.dashboard', absolute: false)];

        foreach ($privateUrls as $url) {
            expect(collect(config('seo.disallow'))->contains(fn (string $prefix) => str_starts_with($url, $prefix)))->toBeTrue("{$url} is blocked");
        }
    });
});

describe('sitemap content', function () {
    beforeEach(function () {
        $this->seed(PageSeeder::class);
    });

    it('lists the public pages: home, categories, products, services, CMS pages, FAQ and contact', function () {
        $category = Category::factory()->create();
        $product = Product::factory()->for($category)->create();
        $service = Service::factory()->create();

        $urls = sitemapUrls(app(SitemapGenerator::class)->build()->render());

        expect($urls)->toContain(route('home'))
            ->toContain(route('categories.show', $category->slug))
            ->toContain(route('products.show', $product->slug))
            ->toContain(route('services.index'))
            ->toContain(route('services.show', $service->slug))
            ->toContain(route('faq'))
            ->toContain(route('contact'));

        foreach (PageKey::cases() as $key) {
            expect($urls)->toContain(route('pages.'.$key->value));
        }
    });

    it('leaves out hidden content', function () {
        $category = Category::factory()->create();
        $hiddenCategory = Category::factory()->inactive()->create();
        $inactiveProduct = Product::factory()->for($category)->inactive()->create();
        $inHiddenCategory = Product::factory()->for($hiddenCategory)->create();
        $deleted = Product::factory()->for($category)->create();
        $deleted->delete();
        $inactiveService = Service::factory()->inactive()->create();
        Page::where('key', PageKey::Terms)->update(['is_published' => false]);

        $urls = sitemapUrls(app(SitemapGenerator::class)->build()->render());

        expect($urls)->not->toContain(route('categories.show', $hiddenCategory->slug))
            ->not->toContain(route('products.show', $inactiveProduct->slug))
            ->not->toContain(route('products.show', $inHiddenCategory->slug))
            ->not->toContain(route('products.show', $deleted->slug))
            ->not->toContain(route('services.show', $inactiveService->slug))
            ->not->toContain(route('pages.terms'));
    });

    it('never lists private or utility pages', function () {
        $xml = app(SitemapGenerator::class)->build()->render();

        foreach (['/admin', '/compte', '/panier', '/commande', '/recherche', '/connexion', '/inscription', '/devis'] as $path) {
            expect($xml)->not->toContain(url($path));
        }
    });

    it('lists every URL once, absolute and without query strings', function () {
        Product::factory()->count(3)->create();

        $urls = sitemapUrls(app(SitemapGenerator::class)->build()->render());

        expect($urls)->toBe(array_values(array_unique($urls)))
            ->and(collect($urls)->every(fn (string $url) => str_starts_with($url, 'http') && ! str_contains($url, '?')))->toBeTrue();
    });

    it('gives content its last modification date', function () {
        $product = Product::factory()->create();
        $product->forceFill(['updated_at' => now()->subDays(3)])->saveQuietly();

        $xml = app(SitemapGenerator::class)->build()->render();

        expect($xml)->toMatch('#<loc>'.preg_quote(route('products.show', $product->slug), '#').'</loc>\s*<lastmod>'.now()->subDays(3)->format('Y-m-d').'#');
    });

    it('is valid XML in the sitemap namespace', function () {
        Product::factory()->create();

        $document = new DOMDocument;

        expect($document->loadXML(app(SitemapGenerator::class)->build()->render()))->toBeTrue()
            ->and($document->documentElement->nodeName)->toBe('urlset')
            ->and($document->documentElement->namespaceURI)->toBe('http://www.sitemaps.org/schemas/sitemap/0.9');
    });

    it('lists the FAQ with the date of its latest question', function () {
        FaqItem::factory()->create();

        $xml = app(SitemapGenerator::class)->build()->render();

        expect($xml)->toMatch('#<loc>'.preg_quote(route('faq'), '#').'</loc>\s*<lastmod>#');
    });
});

describe('sitemap file', function () {
    beforeEach(fn () => File::delete(config('seo.sitemap_path')));

    it('is built on the first request and served as XML', function () {
        Product::factory()->create(['name' => 'Listed product']);

        $this->get('/sitemap.xml')->assertOk()->assertHeader('Content-Type', 'application/xml; charset=UTF-8')->assertSee('<urlset', false)->assertSee('listed-product', false);

        expect(config('seo.sitemap_path'))->toBeFile();
    });

    it('is written by the artisan command', function () {
        $product = Product::factory()->create();

        $this->artisan('sitemap:generate')->assertSuccessful();

        expect(File::get(config('seo.sitemap_path')))->toContain(route('products.show', $product->slug));
    });

    it('is regenerated daily by the scheduler', function () {
        $this->artisan('schedule:list')->assertSuccessful()->expectsOutputToContain('sitemap:generate');
    });

    it('is rebuilt by the queued job', function () {
        $product = Product::factory()->create();

        (new RegenerateSitemap)->handle(app(SitemapGenerator::class));

        expect(File::get(config('seo.sitemap_path')))->toContain(route('products.show', $product->slug));
    });
});

describe('regeneration on publish', function () {
    beforeEach(function () {
        $this->seed(PageSeeder::class);
        Queue::fake([RegenerateSitemap::class]);
    });

    it('is queued when a product, category, service, page or question is created', function (callable $create) {
        $create();

        Queue::assertPushed(RegenerateSitemap::class);
    })->with([
        'product' => [fn () => Product::factory()->create()],
        'category' => [fn () => Category::factory()->create()],
        'service' => [fn () => Service::factory()->create()],
        'faq item' => [fn () => FaqItem::factory()->create()],
    ]);

    it('is queued when a page is unpublished', function () {
        Page::where('key', PageKey::About)->firstOrFail()->update(['is_published' => false]);

        Queue::assertPushed(RegenerateSitemap::class, 1);
    });

    it('is queued when a page is published', function () {
        Page::where('key', PageKey::About)->firstOrFail()->update(['is_published' => false]);
        resetSitemapQueue();

        Page::where('key', PageKey::About)->firstOrFail()->update(['is_published' => true]);

        Queue::assertPushed(RegenerateSitemap::class, 1);
    });

    it('is queued when a product is deactivated', function () {
        $product = Product::factory()->create();
        resetSitemapQueue();

        $product->update(['is_active' => false]);

        Queue::assertPushed(RegenerateSitemap::class, 1);
    });

    it('is queued when a product is removed', function () {
        $product = Product::factory()->create();
        resetSitemapQueue();

        $product->delete();

        Queue::assertPushed(RegenerateSitemap::class, 1);
    });

    it('is queued when a slug changes', function () {
        $service = Service::factory()->create();
        resetSitemapQueue();

        $service->update(['slug' => 'new-slug']);

        Queue::assertPushed(RegenerateSitemap::class, 1);
    });

    it('is not queued for edits that do not change the sitemap', function () {
        $product = Product::factory()->create();
        resetSitemapQueue();
        $page = Page::where('key', PageKey::About)->firstOrFail();

        $product->update(['stock_quantity' => 3, 'description' => 'New text', 'name' => 'Renamed']);
        $product->update(['price_ht' => 5000]);
        $page->update(['content' => 'New content', 'title' => 'New title']);

        Queue::assertNotPushed(RegenerateSitemap::class);
    });

    it('queues a burst of changes only once', function () {
        Product::factory()->count(3)->create();

        Queue::assertPushed(RegenerateSitemap::class, 1);
    });

    it('is de-duplicated: the job is unique and runs after commit', function () {
        $job = new RegenerateSitemap;

        expect($job)->toBeInstanceOf(ShouldBeUnique::class)
            ->and($job->afterCommit)->toBeTrue()
            ->and($job->uniqueFor)->toBeGreaterThan(0);
    });
});
