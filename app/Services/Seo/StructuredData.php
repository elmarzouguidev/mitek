<?php

namespace App\Services\Seo;

use App\Models\Core\Content\FaqItem;
use App\Models\Core\Product\Product;
use App\Models\Core\Service\Service;
use App\Services\Core\Product\PriceCalculator;
use App\Settings\GeneralSettings;
use App\Support\Seo;
use Illuminate\Support\Collection;

/**
 * Builds the schema.org JSON-LD documents of the storefront from the settings and the content.
 */
class StructuredData
{
    public const CONTEXT = 'https://schema.org';

    public function __construct(
        private readonly GeneralSettings $settings,
        private readonly PriceCalculator $prices,
    ) {}

    /**
     * Organization and LocalBusiness, emitted on every page.
     *
     * @return array<string, mixed>
     */
    public function organization(): array
    {
        $organizationId = url('/').'#organization';

        $address = array_filter([
            '@type' => 'PostalAddress',
            'streetAddress' => $this->settings->registered_address ?: null,
            'addressLocality' => $this->settings->city,
            'addressCountry' => 'MA',
        ]);

        $contactPoint = array_filter([
            '@type' => 'ContactPoint',
            'contactType' => 'customer service',
            'email' => $this->settings->email,
            'telephone' => $this->settings->phone ?: null,
            'availableLanguage' => 'fr',
            'areaServed' => 'MA',
        ]);

        $shared = [
            'name' => $this->settings->site_name,
            'legalName' => $this->settings->legal_name,
            'url' => url('/'),
            'logo' => asset('images/logo-mitek.png'),
            'image' => asset('images/share-default.png'),
            'email' => $this->settings->email,
            'telephone' => $this->settings->phone ?: null,
            'address' => $address,
        ];

        return [
            '@context' => self::CONTEXT,
            '@graph' => [
                array_filter(['@type' => 'Organization', '@id' => $organizationId, ...$shared, 'contactPoint' => $contactPoint]),
                array_filter(['@type' => 'LocalBusiness', '@id' => url('/').'#localbusiness', ...$shared, 'parentOrganization' => ['@id' => $organizationId]]),
            ],
        ];
    }

    /**
     * @param  array<string, string|null>  $items  label => url (the last item may have no url: it is the current page)
     * @return array<string, mixed>
     */
    public function breadcrumbs(array $items): array
    {
        $position = 1;
        $elements = [[
            '@type' => 'ListItem',
            'position' => $position++,
            'name' => __('catalog.home'),
            'item' => route('home'),
        ]];

        foreach ($items as $label => $url) {
            $elements[] = [
                '@type' => 'ListItem',
                'position' => $position++,
                'name' => (string) $label,
                'item' => $url ?: url()->current(),
            ];
        }

        return ['@context' => self::CONTEXT, '@type' => 'BreadcrumbList', 'itemListElement' => $elements];
    }

    /**
     * Product with an Offer: price TTC in MAD and availability. The product's `priceTiers`, `brand` and
     * `media` must be loaded.
     *
     * @return array<string, mixed>
     */
    public function product(Product $product): array
    {
        $image = $product->getFirstMediaUrl(Product::GALLERY, 'large');

        return array_filter([
            '@context' => self::CONTEXT,
            '@type' => 'Product',
            'name' => $product->name,
            'sku' => $product->reference,
            'description' => Seo::description($product->excerpt ?: $product->description),
            'image' => $image ?: null,
            'brand' => $product->brand ? ['@type' => 'Brand', 'name' => $product->brand->name] : null,
            'offers' => [
                '@type' => 'Offer',
                'url' => route('products.show', $product->slug),
                'priceCurrency' => 'MAD',
                'price' => number_format($this->prices->unitPriceTtc($product)->toDecimal(), 2, '.', ''),
                'availability' => $product->isInStock() ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
                'itemCondition' => 'https://schema.org/NewCondition',
                'seller' => ['@id' => url('/').'#organization'],
            ],
        ]);
    }

    /**
     * @param  Collection<int, FaqItem>  $items
     * @return array<string, mixed>
     */
    public function faq(Collection $items, callable $renderAnswer): array
    {
        return [
            '@context' => self::CONTEXT,
            '@type' => 'FAQPage',
            'mainEntity' => $items->map(fn (FaqItem $item): array => [
                '@type' => 'Question',
                'name' => $item->question,
                'acceptedAnswer' => ['@type' => 'Answer', 'text' => trim(strip_tags($renderAnswer($item)))],
            ])->values()->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function service(Service $service): array
    {
        return array_filter([
            '@context' => self::CONTEXT,
            '@type' => 'Service',
            'name' => $service->title,
            'serviceType' => $service->title,
            'description' => $service->summary,
            'url' => route('services.show', $service->slug),
            'provider' => ['@id' => url('/').'#organization'],
            'areaServed' => ['@type' => 'Country', 'name' => 'Maroc'],
        ]);
    }
}
