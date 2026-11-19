<?php

namespace App\Support;

use App\Models\Core\Product\Product;
use App\Services\Core\Product\PriceCalculator;
use App\Settings\GeneralSettings;

/**
 * Builds wa.me links from the WhatsApp number in the general settings (digits only, international format).
 * Every link on the site goes through here, and nothing is rendered while the number is empty.
 */
class WhatsApp
{
    public function __construct(
        private readonly GeneralSettings $settings,
        private readonly PriceCalculator $prices,
    ) {}

    public function isConfigured(): bool
    {
        return filled($this->settings->whatsapp);
    }

    /**
     * @return string|null null while the number is empty
     */
    public function link(?string $message = null): ?string
    {
        if (! $this->isConfigured()) {
            return null;
        }

        $url = 'https://wa.me/'.preg_replace('/\D+/', '', $this->settings->whatsapp);

        return $message === null ? $url : $url.'?text='.rawurlencode($message);
    }

    /**
     * Prefilled order message: name, reference, unit price TTC (from the PriceCalculator) and page URL.
     */
    public function productMessage(Product $product): string
    {
        return __('app.whatsapp.product_message', [
            'name' => $product->name,
            'reference' => $product->reference,
            'price' => $this->prices->unitPriceTtc($product)->format(),
            'url' => route('products.show', $product->slug),
        ]);
    }

    public function productLink(Product $product): ?string
    {
        return $this->link($this->productMessage($product));
    }
}
