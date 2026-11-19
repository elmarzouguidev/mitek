<?php

namespace App\Enums;

/**
 * The editable CMS pages. Their URL is fixed; administrators edit title, content, SEO and the published flag.
 */
enum PageKey: string
{
    case About = 'about';
    case WhyMitek = 'why-mitek';
    case Payment = 'payment';
    case Delivery = 'delivery';
    case LegalNotice = 'legal-notice';
    case Terms = 'terms';
    case Privacy = 'privacy';

    public function slug(): string
    {
        return match ($this) {
            self::About => 'a-propos',
            self::WhyMitek => 'pourquoi-mitek',
            self::Payment => 'modes-de-paiement',
            self::Delivery => 'modes-de-livraison',
            self::LegalNotice => 'mentions-legales',
            self::Terms => 'cgv',
            self::Privacy => 'politique-de-confidentialite',
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::About => 'À propos',
            self::WhyMitek => 'Pourquoi Mitek',
            self::Payment => 'Modes de paiement',
            self::Delivery => 'Modes de livraison',
            self::LegalNotice => 'Mentions légales',
            self::Terms => 'Conditions générales de vente',
            self::Privacy => 'Politique de confidentialité',
        };
    }

    /**
     * Marketing pages follow the design-taste-frontend direction; the others are plain utility or legal pages.
     */
    public function isMarketing(): bool
    {
        return in_array($this, [self::About, self::WhyMitek], true);
    }

    /**
     * FAQ category shown under the page, when there is one.
     */
    public function faqCategory(): ?string
    {
        return match ($this) {
            self::Payment => 'paiement',
            self::Delivery => 'livraison',
            default => null,
        };
    }
}
