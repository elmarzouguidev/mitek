<?php

namespace App\Services\Content;

use App\Services\Core\Shipping\DeliveryEstimator;
use App\Settings\GeneralSettings;
use App\Settings\PaymentSettings;
use App\Settings\ShippingSettings;
use App\Support\Money;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

/**
 * Renders editable content (Markdown) to safe HTML. `{{token}}` placeholders are replaced from the
 * settings at render time, so business values (COD limit, delays, bank holder, company identifiers)
 * are never typed into a page. An empty setting prints "[À COMPLÉTER]" instead of disappearing.
 */
class ContentRenderer
{
    public const PLACEHOLDER = '[À COMPLÉTER]';

    public function __construct(
        private readonly GeneralSettings $general,
        private readonly PaymentSettings $payment,
        private readonly ShippingSettings $shipping,
        private readonly DeliveryEstimator $delivery,
    ) {}

    public function html(string $content): HtmlString
    {
        $markdown = Str::markdown($this->replaceTokens($content), [
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
        ]);

        return new HtmlString($markdown);
    }

    /**
     * Plain text of a rendered value (for meta tags and JSON-LD).
     */
    public function text(string $content): string
    {
        return Str::squish(html_entity_decode(strip_tags($this->html($content)->toHtml()), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    }

    public function replaceTokens(string $content): string
    {
        $values = $this->values();

        return (string) preg_replace_callback('/\{\{\s*([a-z_]+)\s*\}\}/', function (array $match) use ($values): string {
            if (! array_key_exists($match[1], $values)) {
                return $match[0];
            }

            $value = $values[$match[1]];

            return filled($value) ? (string) $value : self::PLACEHOLDER;
        }, $content);
    }

    /**
     * Token => value, also listed in the back office editor.
     *
     * @return array<string, string|int|null>
     */
    public function values(): array
    {
        return [
            'site_name' => $this->general->site_name,
            'legal_name' => $this->general->legal_name,
            'legal_form' => $this->general->legal_form,
            'share_capital' => $this->general->share_capital,
            'address' => $this->general->address,
            'registered_address' => $this->general->registered_address ?: $this->general->address,
            'city' => $this->general->city,
            'email' => $this->general->email,
            'phone' => $this->general->phone,
            'ice' => $this->general->ice,
            'if' => $this->general->if_number,
            'rc' => $this->general->rc_number,
            'tp' => $this->general->tp_number,
            'cnss' => $this->general->cnss_number,
            'vat_rate' => $this->general->vat_rate,
            'cod_limit' => Money::fromCents($this->payment->cod_limit_cents)->format(),
            'bank_name' => $this->payment->bank_name,
            'account_holder' => $this->payment->account_holder,
            'rib' => $this->payment->rib,
            'cheque_payee' => $this->payment->cheque_payee,
            'bank_transfer_delay' => $this->payment->bank_transfer_delay,
            'cash_deposit_delay' => $this->payment->cash_deposit_delay,
            'cheque_delay' => $this->payment->cheque_delay,
            'dispatch_days' => $this->shipping->dispatch_days,
            'delivery_estimate' => $this->delivery->summaryText(),
            'own_carrier_fee' => $this->shipping->own_carrier_fee_cents > 0 ? Money::fromCents($this->shipping->own_carrier_fee_cents)->format() : null,
        ];
    }
}
