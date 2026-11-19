<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

/**
 * Business claims that are not confirmed in Mitek.md. Every one is off by default and controls where the
 * claim may appear on the site: nothing unconfirmed is ever shown. Claims already confirmed in
 * Mitek.md (certified technical team, customer service 6 days a week, monthly maintenance contracts,
 * detailed invoice with every order, delivery everywhere in Morocco, quantity pricing) need no switch.
 */
class ClaimsSettings extends Settings
{
    /** "Devis sous 24 h ouvrées". */
    public bool $quote_within_24h;

    /** "Interlocuteur dédié". */
    public bool $dedicated_contact;

    /** RC, IF, ICE, RIB and tax-compliance documents available on request. */
    public bool $admin_documents_on_request;

    /** Pro-forma invoices can be issued. */
    public bool $proforma_available;

    /** Customers can collect their order in Casablanca. */
    public bool $pickup_casablanca;

    /** Show the warranty line (text from the general settings) on product pages. */
    public bool $show_warranty_lines;

    public static function group(): string
    {
        return 'claims';
    }

    /**
     * Setting key => label and help shown in the back office.
     *
     * @return array<string, array{label: string, help: string}>
     */
    public static function descriptions(): array
    {
        return [
            'quote_within_24h' => ['label' => 'Devis sous 24 h ouvrées', 'help' => 'Affiche l\'engagement de réponse sous 24 h sur les pages devis et Espace pro.'],
            'dedicated_contact' => ['label' => 'Interlocuteur dédié', 'help' => 'Affiche « interlocuteur dédié » pour les entreprises.'],
            'admin_documents_on_request' => ['label' => 'Documents administratifs sur demande', 'help' => 'RC, IF, ICE, RIB, attestation de régularité fiscale.'],
            'proforma_available' => ['label' => 'Facture pro-forma disponible', 'help' => 'Affiche la possibilité d\'obtenir une facture pro-forma.'],
            'pickup_casablanca' => ['label' => 'Retrait à Casablanca', 'help' => 'Affiche le retrait sur place comme mode d\'obtention du produit.'],
            'show_warranty_lines' => ['label' => 'Afficher la garantie sur les produits', 'help' => 'Utilise le texte de garantie défini dans les paramètres généraux.'],
        ];
    }
}
