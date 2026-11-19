<?php

namespace Database\Seeders;

use App\Models\Core\Service\Service;
use Illuminate\Database\Seeder;

/**
 * The nine IT services of Mitek.md, with their bullet points.
 */
class ServiceSeeder extends Seeder
{
    /**
     * @var array<int, array{title: string, icon: string, summary: string, bullets: array<int, string>}>
     */
    private const SERVICES = [
        ['title' => 'Maintenance informatique & support technique', 'icon' => 'wrench', 'summary' => 'Diagnostic, réparation et maintenance préventive de votre parc, à distance ou sur site.',
            'bullets' => ['Diagnostic et réparation de matériel', 'Maintenance préventive', 'Support à distance ou sur site', 'Contrats mensuels pour entreprises']],
        ['title' => 'Installation de réseaux & Wi-Fi professionnel', 'icon' => 'network', 'summary' => 'Réseaux Ethernet et Wi-Fi installés, sécurisés et optimisés pour vos locaux.',
            'bullets' => ['Installation de réseaux Ethernet, routeurs, switchs, bornes Wi-Fi', 'Sécurisation et optimisation de la connexion', 'Étude de couverture Wi-Fi dans les locaux']],
        ['title' => 'Cybersécurité & protection des données', 'icon' => 'shield', 'summary' => 'Antivirus, audit de sécurité et sauvegardes pour protéger vos données.',
            'bullets' => ['Antivirus professionnels', 'Audit de sécurité', 'Sauvegardes automatiques', 'Protection contre ransomwares et phishing']],
        ['title' => 'Services cloud pour PME', 'icon' => 'cloud', 'summary' => 'Migration et solutions collaboratives dans le cloud pour les petites et moyennes entreprises.',
            'bullets' => ['Migration vers Google Workspace ou Microsoft 365', 'Sauvegardes cloud (OneDrive, Dropbox Business…)', 'Mise en place de solutions collaboratives']],
        ['title' => 'Récupération de données', 'icon' => 'database', 'summary' => 'Récupération sur disques durs, clés USB et SSD, y compris après formatage ou panne.',
            'bullets' => ['Récupération sur disques durs, clés USB, SSD', 'Clonage de disques et récupération après formatage ou panne']],
        ['title' => 'Gestion de parc informatique & bureautique', 'icon' => 'desktop', 'summary' => 'Suivi de vos postes de travail, installation de logiciels et dépannage des périphériques.',
            'bullets' => ['Suivi des postes de travail', 'Installation de logiciels et drivers', 'Dépannage d\'imprimantes et périphériques']],
        ['title' => 'Assemblage & configuration de PC sur mesure', 'icon' => 'chip', 'summary' => 'Des PC montés selon vos besoins : CAO, bureautique, gaming…',
            'bullets' => ['Montage de PC adaptés aux besoins spécifiques (CAO, bureautique, gaming…)', 'Conseils personnalisés']],
        ['title' => 'Audit informatique & conseil', 'icon' => 'clipboard', 'summary' => 'Analyse de votre environnement informatique et accompagnement de votre transformation numérique.',
            'bullets' => ['Analyse de l\'environnement informatique', 'Conseils d\'optimisation matériel/logiciel', 'Accompagnement transformation numérique']],
        ['title' => 'Services complémentaires', 'icon' => 'plus', 'summary' => 'Emails professionnels, signatures mail et mini-formations pour vos équipes.',
            'bullets' => ['Création d\'emails professionnels', 'Signatures mail pro', 'Mini-formations pour les employés']],
    ];

    public function run(): void
    {
        foreach (self::SERVICES as $position => $service) {
            Service::firstOrCreate(['title' => $service['title']], [...$service, 'sort_order' => $position + 1]);
        }
    }
}
