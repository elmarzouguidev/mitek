<?php

namespace Database\Seeders;

use App\Models\Core\Product\Category;
use Illuminate\Database\Seeder;

/**
 * The five product families of Mitek.md, with subcategories taken from their bullet points.
 */
class CategorySeeder extends Seeder
{
    /**
     * @var array<string, array{description: string, children: array<string, string>}>
     */
    private const TREE = [
        'Consommables & Toners' => [
            'description' => "Cartouches et toners d'origine pour HP, Lexmark, Canon et Brother, ainsi que les rubans pour étiqueteuses.",
            'children' => [
                'Toners HP' => "Toners HP d'origine (ex. HP 312A).",
                'Toners Lexmark' => "Toners Lexmark d'origine (ex. 55B5000).",
                'Toners Canon' => "Cartouches et toners Canon d'origine.",
                'Toners Brother' => "Toners Brother d'origine.",
                'Rubans pour étiqueteuses' => 'Rubans Brother TZe pour étiqueteuses (ex. TZe-551, TZe-651).',
            ],
        ],
        'Équipements informatiques & Accessoires' => [
            'description' => 'Switchs réseau PoE, tablettes graphiques, claviers et souris sans fil.',
            'children' => [
                'Réseau et switchs PoE' => 'Switchs réseau PoE et accessoires de câblage.',
                'Tablettes graphiques' => 'Tablettes graphiques Wacom Intuos.',
                'Claviers et souris' => 'Claviers et souris, filaires ou sans fil.',
            ],
        ],
        'Matériel de bureau & Papeterie' => [
            'description' => 'Étiqueteuses professionnelles, ensembles de bureau, blocs-notes, carnets, stylos et surligneurs.',
            'children' => [
                'Étiqueteuses professionnelles' => 'Étiqueteuses Brother pour le bureau et les ateliers.',
                'Ensembles de bureau' => 'Ensembles de bureau en simili-cuir (7 à 9 pièces).',
                'Blocs-notes et carnets' => 'Blocs-notes et carnets de toutes tailles.',
                'Stylos et surligneurs' => 'Stylos gel, stylos plume et surligneurs.',
            ],
        ],
        'Mobilier & Organisation du travail' => [
            'description' => 'Chaises ergonomiques, bureaux, équipements coworking et accessoires de rangement.',
            'children' => [
                'Chaises ergonomiques' => 'Chaises de bureau ergonomiques.',
                'Bureaux et coworking' => 'Bureaux et tables pour espaces de coworking.',
                'Rangement et archivage' => "Porte-documents, classeurs, boîtes d'archivage et corbeilles à courrier.",
            ],
        ],
        'Autres consommables & Hygiène' => [
            'description' => "Fournitures bureautiques, produits d'hygiène et d'entretien, emballage.",
            'children' => [
                'Fournitures bureautiques' => 'Agrafes, trombones et rubans adhésifs.',
                'Hygiène et entretien' => 'Sacs poubelle, désinfectants et gants.',
                'Emballage' => 'Film bulle et cartons.',
            ],
        ],
    ];

    public function run(): void
    {
        $position = 0;

        foreach (self::TREE as $name => $family) {
            $root = Category::firstOrCreate(['name' => $name], [
                'description' => $family['description'],
                'sort_order' => ++$position,
            ]);

            $childPosition = 0;

            foreach ($family['children'] as $childName => $description) {
                Category::firstOrCreate(['name' => $childName], [
                    'parent_id' => $root->id,
                    'description' => $description,
                    'sort_order' => ++$childPosition,
                ]);
            }
        }
    }
}
