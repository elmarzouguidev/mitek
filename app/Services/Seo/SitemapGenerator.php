<?php

namespace App\Services\Seo;

use App\Models\Core\Content\FaqItem;
use App\Models\Core\Content\Page;
use App\Models\Core\Product\Category;
use App\Models\Core\Product\Product;
use App\Models\Core\Service\Service;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;
use Spatie\Sitemap\Sitemap;
use Spatie\Sitemap\Tags\Url;

/**
 * Builds sitemap.xml from the public content: home, categories, products, services, CMS pages, FAQ and
 * contact, each with its last modification date. Private and utility pages are never listed.
 */
class SitemapGenerator
{
    public function build(): Sitemap
    {
        $sitemap = Sitemap::create();

        $sitemap->add($this->url(route('home'), null, 1.0));
        $sitemap->add($this->url(route('services.index'), Service::query()->active()->max('updated_at'), 0.8));

        Category::query()->visible()->get(['slug', 'updated_at'])->each(
            fn (Category $category) => $sitemap->add($this->url(route('categories.show', $category->slug), $category->updated_at, 0.8)),
        );

        Product::query()->visible()->get(['slug', 'updated_at'])->each(
            fn (Product $product) => $sitemap->add($this->url(route('products.show', $product->slug), $product->updated_at, 0.7)),
        );

        Service::query()->active()->get(['slug', 'updated_at'])->each(
            fn (Service $service) => $sitemap->add($this->url(route('services.show', $service->slug), $service->updated_at, 0.7)),
        );

        Page::query()->published()->get(['key', 'updated_at'])->each(
            fn (Page $page) => $sitemap->add($this->url(route('pages.'.$page->key->value), $page->updated_at, 0.5)),
        );

        $sitemap->add($this->url(route('faq'), FaqItem::query()->active()->max('updated_at'), 0.5));
        $sitemap->add($this->url(route('contact'), null, 0.5));

        return $sitemap;
    }

    /**
     * Writes the sitemap to the configured path and returns it.
     */
    public function write(): string
    {
        $path = config('seo.sitemap_path');
        File::ensureDirectoryExists(dirname($path));
        $this->build()->writeToFile($path);

        return $path;
    }

    private function url(string $url, CarbonInterface|string|null $lastModified, float $priority): Url
    {
        $tag = Url::create($url)->setPriority($priority);

        if ($lastModified !== null) {
            $tag->setLastModificationDate(Carbon::parse($lastModified));
        }

        return $tag;
    }
}
