<?php

namespace Database\Seeders;

use App\Enums\PageKey;
use App\Models\Core\Content\Page;
use Illuminate\Database\Seeder;

/**
 * CMS pages seeded from Mitek.md. Legal text that is not in Mitek.md is left as visible [À COMPLÉTER]
 * placeholders for the owner (and a lawyer) to fill in from the back office.
 */
class PageSeeder extends Seeder
{
    public function run(): void
    {
        foreach (PageKey::cases() as $key) {
            Page::firstOrCreate(['key' => $key], [
                'slug' => $key->slug(),
                'title' => $key->label(),
                'content' => $this->content($key),
                'is_published' => true,
            ]);
        }
    }

    private function content(PageKey $key): string
    {
        return match ($key) {
            PageKey::About => <<<'MD'
{{site_name}} est une entreprise marocaine spécialisée dans la distribution de matériel de bureau et de solutions informatiques.

Notre mission est d'accompagner les entreprises dans leur performance avec des produits fiables, un service client réactif et une logistique optimisée.

## Nos valeurs

- **Fiabilité**
- **Proximité**
- **Innovation**
MD,
            PageKey::WhyMitek => <<<'MD'
## Pourquoi choisir {{site_name}} ?

- **Une équipe technique certifiée** et à l'écoute
- **Des produits testés** et sélectionnés
- **Des prix justes** et transparents
- **Une livraison rapide** partout au Maroc
- **Un service client** disponible 6j/7
MD,
            PageKey::Payment => <<<'MD'
Sur {{site_name}}, nous facilitons vos achats avec plusieurs options fiables. Il n'y a pas de paiement par carte en ligne : après votre commande, vous envoyez votre justificatif depuis la page de suivi, et notre équipe le valide manuellement.

## 1. Virement bancaire

- Compte : {{account_holder}}, {{bank_name}}
- RIB : {{rib}}
- Envoyez le justificatif de votre virement depuis la page de votre commande.
- Le transfert prend généralement {{bank_transfer_delay}}.

## 2. Dépôt d'espèces en agence

- Même compte que ci-dessus.
- Envoyez-nous une copie de votre dépôt (scan recto-verso).
- La validation prend {{cash_deposit_delay}}.

## 3. Paiement à la livraison

- Disponible jusqu'à {{cod_limit}} TTC.
- Choisissez-le simplement lors de votre commande : notre équipe confirme votre commande avant l'expédition.

## 4. Chèque certifié ou chèque de banque

- À l'ordre de {{cheque_payee}}.
- Envoyez une copie recto-verso depuis la page de votre commande ; encaissement sous {{cheque_delay}}.

## Sécurité et légalité

Ces solutions respectent les lois marocaines : intégrité, transparence et lutte contre la fraude sont nos priorités.
MD,
            PageKey::Delivery => <<<'MD'
{{site_name}} s'engage à une expédition rapide et un suivi simplifié.

- **Expédition** dans les {{dispatch_days}} jours suivant la validation du paiement, avec suivi via transporteur.
- **Délai de livraison :** en général, {{delivery_estimate}}.
- **Votre propre transporteur :** si vous souhaitez l'utiliser, indiquez-le lors de la commande : un coût supplémentaire peut s'appliquer.
- **Frais de livraison :** variables selon le poids et le type d'emballage (calculés à la commande selon votre ville). Nous vous conseillons de regrouper vos articles en une seule commande.
- **Emballage :** chaque colis est bien emballé et protégé, y compris les produits fragiles.
MD,
            PageKey::LegalNotice => <<<'MD'
## Éditeur du site

- Raison sociale : {{legal_name}} ({{legal_form}})
- Capital social : {{share_capital}}
- Siège social : {{registered_address}}
- Email : {{email}}
- Téléphone : {{phone}}

## Identification

- RC : {{rc}}
- Identifiant fiscal (IF) : {{if}}
- ICE : {{ice}}
- Patente (TP) : {{tp}}
- CNSS : {{cnss}}

## Directeur de la publication

[À COMPLÉTER]

## Hébergement

[À COMPLÉTER : nom, adresse et contact de l'hébergeur du site]

## Propriété intellectuelle

[À COMPLÉTER : texte juridique sur les droits d'auteur, marques et contenus du site]
MD,
            PageKey::Terms => <<<'MD'
Les présentes conditions générales de vente s'appliquent aux commandes passées sur {{site_name}} par des professionnels et des particuliers.

## 1. Prix

Les prix sont indiqués en dirhams marocains (MAD), hors taxe (HT) et toutes taxes comprises (TTC). Le taux de TVA applicable est de {{vat_rate}} %. Des tarifs dégressifs sont disponibles selon la quantité.

## 2. Commande

[À COMPLÉTER : conditions de validation de la commande, disponibilité des produits, annulation]

## 3. Paiement

Les modes de paiement acceptés sont le virement bancaire, le dépôt d'espèces en agence, le chèque certifié ou chèque de banque et, jusqu'à {{cod_limit}} TTC, le paiement à la livraison. Il n'y a pas de paiement par carte en ligne. Le paiement est validé manuellement par notre équipe après réception du justificatif. Chèque à l'ordre de {{cheque_payee}}.

## 4. Livraison

Les commandes sont expédiées dans les {{dispatch_days}} jours suivant la validation du paiement. Délai de livraison indicatif : {{delivery_estimate}}. Les frais de livraison dépendent du poids et de la ville de livraison.

## 5. Facturation

Chaque commande donne lieu à une facture détaillée. Les clients professionnels peuvent renseigner leur raison sociale, leur ICE, leur IF et leur RC pour qu'ils figurent sur la facture.

## 6. Retours, garanties et réclamations

[À COMPLÉTER : droit de rétractation, conditions de retour, garantie légale et commerciale, traitement des réclamations]

## 7. Responsabilité

[À COMPLÉTER : limitation de responsabilité, force majeure]

## 8. Droit applicable et litiges

[À COMPLÉTER : droit applicable, juridiction compétente, règlement amiable des litiges]
MD,
            PageKey::Privacy => <<<'MD'
{{legal_name}} attache de l'importance à la protection de vos données personnelles, conformément à la **loi n° 09-08** relative à la protection des personnes physiques à l'égard du traitement des données à caractère personnel, et sous le contrôle de la **Commission nationale de contrôle de la protection des données à caractère personnel (CNDP)**.

## Responsable du traitement

{{legal_name}}, {{registered_address}}. Contact : {{email}}.

## Données collectées

Nous collectons les données que vous nous transmettez : lors de la création d'un compte et d'une commande (nom, email, téléphone, adresse de livraison, informations d'entreprise comme l'ICE, l'IF et le RC), dans les formulaires de contact, de demande de devis et de demande de service, ainsi que les justificatifs de paiement que vous envoyez.

## Finalités

Traiter et livrer vos commandes, établir vos factures, valider vos paiements, répondre à vos demandes (contact, devis, services), et respecter nos obligations légales et comptables.

## Destinataires

[À COMPLÉTER : prestataires destinataires des données (transporteurs, hébergeur, service d'envoi d'emails…)]

## Durée de conservation

[À COMPLÉTER : durées de conservation des données et des documents comptables]

## Vos droits

Conformément à la loi 09-08, vous disposez d'un droit d'accès, de rectification et d'opposition concernant vos données. Pour l'exercer, écrivez-nous à {{email}}. Vous pouvez également adresser une réclamation à la CNDP.

## Déclaration à la CNDP

[À COMPLÉTER : numéro de la déclaration ou de l'autorisation CNDP]

## Cookies

Le site utilise des cookies techniques indispensables à son fonctionnement (session, panier, sécurité). [À COMPLÉTER : mention des éventuels cookies de mesure d'audience]
MD,
        };
    }
}
