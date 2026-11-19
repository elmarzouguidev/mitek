<?php

use App\Jobs\RegenerateSitemap;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind different classes or traits.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->beforeEach(function () {
        $this->seed(RoleSeeder::class);
        Storage::fake('local');
        Storage::fake('public');
        // Regenerating the sitemap on every model save would slow the suite; tests that need it call the generator.
        Queue::fake([RegenerateSitemap::class]);
        config(['seo.sitemap_path' => sys_get_temp_dir().'/mitek-sitemap-test-'.getmypid().'.xml']);
    })
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function something()
{
    // ..
}

/*
|--------------------------------------------------------------------------
| Shared helpers
|--------------------------------------------------------------------------
*/

use App\Models\Core\Product\Product;
use App\Models\Core\Shipping\ShippingZone;
use Illuminate\Support\Facades\Cache;

/**
 * Makes shipping available: a default zone with a single flat rate (in dirhams HT).
 */
function enableShipping(string|int|float $price = 30): ShippingZone
{
    return ShippingZone::factory()->default()->flatRate($price)->create(['name' => 'Default zone']);
}

/**
 * Session content of a cart: [product => quantity].
 *
 * @param  array<int, array{0: Product, 1: int}>  $lines
 * @return array{cart: array<int, int>}
 */
function cartSession(array $lines): array
{
    return ['cart' => collect($lines)->mapWithKeys(fn (array $line): array => [$line[0]->id => $line[1]])->all()];
}

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function checkoutPayload(array $overrides = []): array
{
    return array_merge([
        'contact_name' => 'Sara Benali',
        'contact_email' => 'sara@example.com',
        'contact_phone' => '0612345678',
        'is_company' => false,
        'shipping_city' => 'Casablanca',
        'shipping_address' => '12 rue des Fleurs',
        'carrier_mode' => 'mitek',
        'payment_method' => 'bank_transfer',
    ], $overrides);
}

/**
 * All JSON-LD nodes of an HTML document (the nodes of any `@graph` are flattened).
 *
 * @return array<int, array<string, mixed>>
 */
function jsonLdNodes(string $html): array
{
    preg_match_all('#<script type="application/ld\+json">(.*?)</script>#s', $html, $matches);

    $nodes = [];

    foreach ($matches[1] as $json) {
        $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);

        foreach ($data['@graph'] ?? [$data] as $node) {
            $nodes[] = $node;
        }
    }

    return $nodes;
}

/**
 * @return array<int, array<string, mixed>>
 */
function jsonLdOfType(string $html, string $type): array
{
    return array_values(array_filter(jsonLdNodes($html), fn (array $node): bool => in_array($type, (array) ($node['@type'] ?? []), true)));
}

/**
 * Heading levels of a document, in order (h1 = 1).
 *
 * @return array<int, int>
 */
function headingLevels(string $html): array
{
    preg_match_all('#<h([1-6])[\s>]#i', $html, $matches);

    return array_map('intval', $matches[1]);
}

/**
 * Asserts a logical outline: one h1, first heading is the h1, no skipped level.
 */
function assertHeadingOutline(string $html): void
{
    $levels = headingLevels($html);

    expect(array_count_values($levels)[1] ?? 0)->toBe(1, 'exactly one h1');
    expect($levels[0])->toBe(1, 'the first heading is the h1');

    for ($index = 1; $index < count($levels); $index++) {
        expect($levels[$index])->toBeLessThanOrEqual($levels[$index - 1] + 1, 'no skipped heading level at position '.$index);
    }
}

/**
 * Forgets the pending sitemap job: it is unique, so within one test only its first dispatch is queued.
 */
function resetSitemapQueue(): void
{
    Cache::lock('laravel_unique_job:'.RegenerateSitemap::class)->forceRelease();
    Queue::fake([RegenerateSitemap::class]);
}
