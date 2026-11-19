<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class GeneralSettings extends Settings
{
    public string $site_name;

    public string $legal_name;

    public string $email;

    /** TODO: phone number not provided in the content file. */
    public ?string $phone;

    /** TODO: WhatsApp number (international format, digits only) not provided yet. */
    public ?string $whatsapp;

    public string $address;

    /** TODO: Google Maps embed URL is a placeholder for now. */
    public ?string $maps_embed_url;

    /** VAT percentage applied to HT prices. */
    public int $vat_rate;

    /** TODO: Mitek's own ICE / IF / RC (printed on invoices) are not provided in Mitek.md. */
    public ?string $ice;

    public ?string $if_number;

    public ?string $rc_number;

    public static function group(): string
    {
        return 'general';
    }
}
