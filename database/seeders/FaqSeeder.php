<?php

namespace Database\Seeders;

use App\Models\Core\Content\FaqCategory;
use Illuminate\Database\Seeder;

/**
 * FAQ built from the facts of Mitek.md. Business values are `{{tokens}}` filled from the settings.
 */
class FaqSeeder extends Seeder
{
    /**
     * @var array<string, array{name: string, items: array<int, array{0: string, 1: string}>}>
     */
    private const FAQ = [
        'general' => ['name' => 'Général', 'items' => [
            ['Puis-je demander une facture ?', 'Bien sûr, chaque commande est accompagnée d\'une facture détaillée.'],
            ['Offrez-vous des réductions pour les entreprises ?', 'Oui, des tarifs préférentiels sont disponibles selon la quantité. Vous pouvez aussi demander un devis depuis votre panier.'],
        ]],
        'livraison' => ['name' => 'Livraison', 'items' => [
            ['Quels sont les délais de livraison ?', 'En général, sous {{delivery_estimate}}.'],
            ['Quand ma commande est-elle expédiée ?', 'Dans les {{dispatch_days}} jours suivant la validation du paiement, avec suivi via transporteur.'],
            ['Puis-je utiliser mon propre transporteur ?', 'Oui. Signalez-le lors de votre commande : un coût supplémentaire peut s\'appliquer.'],
            ['Comment sont calculés les frais de livraison ?', 'Les frais varient selon le poids et le type d\'emballage. Nous vous conseillons de regrouper vos articles en une seule commande.'],
        ]],
        'paiement' => ['name' => 'Paiement', 'items' => [
            ['Quels modes de paiement acceptez-vous ?', 'Le virement bancaire, le dépôt d\'espèces en agence, le paiement à la livraison (dans la limite de {{cod_limit}} TTC) et le chèque certifié ou chèque de banque. Il n\'y a pas de paiement par carte en ligne.'],
            ['Combien de temps faut-il pour valider mon paiement ?', 'Un virement prend généralement {{bank_transfer_delay}}, un dépôt d\'espèces {{cash_deposit_delay}}, et l\'encaissement d\'un chèque {{cheque_delay}}. Envoyez votre justificatif depuis la page de votre commande.'],
            ['Puis-je payer à la livraison ?', 'Oui, pour les commandes jusqu\'à {{cod_limit}} TTC.'],
            ['À l\'ordre de qui établir mon chèque ?', 'À l\'ordre de {{cheque_payee}}.'],
        ]],
        'services' => ['name' => 'Services informatiques', 'items' => [
            ['Intervenez-vous sur site ou à distance ?', 'Les deux : nous proposons un support à distance ou sur site.'],
            ['Proposez-vous des contrats de maintenance ?', 'Oui, des contrats mensuels pour les entreprises.'],
            ['Comment demander un service ?', 'Choisissez le service qui vous intéresse et remplissez le formulaire de demande : notre équipe vous recontacte.'],
        ]],
    ];

    public function run(): void
    {
        $position = 0;

        foreach (self::FAQ as $slug => $category) {
            $model = FaqCategory::firstOrCreate(['slug' => $slug], ['name' => $category['name'], 'sort_order' => ++$position]);

            if ($model->items()->exists()) {
                continue;
            }

            foreach ($category['items'] as $index => [$question, $answer]) {
                $model->items()->create(['question' => $question, 'answer' => $answer, 'sort_order' => $index + 1]);
            }
        }
    }
}
