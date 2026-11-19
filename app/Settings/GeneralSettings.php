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

    /** TODO: patente (taxe professionnelle) number, not provided yet. */
    public ?string $tp_number;

    /** TODO: CNSS affiliation number, not provided yet. */
    public ?string $cnss_number;

    public string $legal_form;

    /** TODO: share capital (e.g. "10 000 DH"), not provided yet. */
    public ?string $share_capital;

    /** TODO: registered address when different from the trading address. */
    public ?string $registered_address;

    public string $city;

    /** Where admin notifications (new order, proof, quote, service request, contact message) are sent. */
    public string $admin_notification_email;

    /** Opening hours shown in the footer and on the contact page; hidden while empty. */
    public ?string $opening_hours;

    /** Hours the WhatsApp line answers, shown next to the floating button; hidden while empty. */
    public ?string $whatsapp_hours;

    public ?string $facebook_url;

    public ?string $instagram_url;

    public ?string $linkedin_url;

    public ?string $youtube_url;

    public ?string $tiktok_url;

    /** TODO: CNDP declaration or authorisation number, shown with the newsletter and privacy notices. */
    public ?string $cndp_number;

    /** Warranty wording shown on product pages when the `show_warranty_lines` claim is on. */
    public ?string $warranty_text;

    public static function group(): string
    {
        return 'general';
    }
}
