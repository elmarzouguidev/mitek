<?php

namespace Database\Seeders;

use App\Models\Core\Product\Brand;
use App\Models\Core\Product\Category;
use App\Models\Core\Product\CompatibleModel;
use App\Models\Core\Product\Product;
use App\Support\Money;
use Illuminate\Database\Seeder;

/**
 * Demo catalogue: every product named in Mitek.md plus enough items to fill each subcategory.
 *
 * DEMO DATA: prices (MAD, HT), weights, stock levels and specifications are plausible placeholders,
 * not Mitek's real catalogue. Replace them from the back office before going live.
 */
class ProductSeeder extends Seeder
{
    private const COMPATIBLE_MODELS = [
        'HP 312A' => ['HP Color LaserJet Pro MFP M476dn', 'HP Color LaserJet Pro MFP M476dw', 'HP Color LaserJet Pro MFP M476nw'],
        'HP 85A' => ['HP LaserJet Pro P1102', 'HP LaserJet Pro P1102w', 'HP LaserJet Pro M1132 MFP', 'HP LaserJet Pro M1212nf MFP'],
        'Lexmark' => ['Lexmark MS331dn', 'Lexmark MS431dn', 'Lexmark MX331adn', 'Lexmark MX431adn'],
        'Canon' => ['Canon i-SENSYS LBP6000', 'Canon i-SENSYS LBP6020', 'Canon i-SENSYS MF3010'],
        'TN-2420' => ['Brother HL-L2310D', 'Brother HL-L2350DW', 'Brother DCP-L2510D', 'Brother MFC-L2710DW'],
        'TZe 24mm' => ['Brother PT-D600VP', 'Brother PT-E550WVP', 'Brother PT-P750W'],
        'TZe 12mm' => ['Brother PT-E110VP', 'Brother PT-D600VP', 'Brother PT-E550WVP', 'Brother PT-P750W'],
    ];

    /** Placeholder colours (RGB) per product family. */
    private const COLORS = [
        'Consommables & Toners' => [219, 234, 254],
        'Équipements informatiques & Accessoires' => [209, 250, 229],
        'Matériel de bureau & Papeterie' => [254, 243, 199],
        'Mobilier & Organisation du travail' => [237, 233, 254],
        'Autres consommables & Hygiène' => [252, 231, 243],
    ];

    public function run(): void
    {
        $this->call([CategorySeeder::class, BrandSeeder::class]);

        foreach ($this->catalogue() as $item) {
            if (Product::withTrashed()->where('reference', $item['sku'])->exists()) {
                continue;
            }

            $category = Category::with('parent')->where('name', $item['category'])->firstOrFail();

            $product = Product::create([
                'category_id' => $category->id,
                'brand_id' => isset($item['brand']) ? Brand::where('name', $item['brand'])->value('id') : null,
                'reference' => $item['sku'],
                'name' => $item['name'],
                'excerpt' => $item['excerpt'],
                'description' => $item['description'] ?? $item['excerpt'],
                'price_ht' => Money::fromDecimal($item['price']),
                'stock_quantity' => $item['stock'] ?? 40,
                'low_stock_threshold' => $item['threshold'] ?? 5,
                'weight_grams' => $item['weight'],
                'is_featured' => $item['featured'] ?? false,
            ]);

            foreach ($item['tiers'] ?? [] as $minQuantity => $price) {
                $product->priceTiers()->create([
                    'min_quantity' => $minQuantity,
                    'price_ht' => Money::fromDecimal($price),
                ]);
            }

            foreach (array_values($item['specs'] ?? []) as $position => [$name, $value]) {
                $product->specifications()->create(['name' => $name, 'value' => $value, 'position' => $position]);
            }

            $product->compatibleModels()->sync(
                collect(self::COMPATIBLE_MODELS[$item['compatible'] ?? ''] ?? [])
                    ->map(fn (string $model): int => CompatibleModel::firstOrCreate(['name' => $model])->id)
                    ->all(),
            );

            $this->attachPlaceholder($product, $category->parent?->name ?? $category->name, $item['sku']);
        }
    }

    private function attachPlaceholder(Product $product, string $family, string $label): void
    {
        $directory = storage_path('app/seed-placeholders');
        is_dir($directory) || mkdir($directory, 0775, true);

        $path = $directory.'/'.preg_replace('/[^A-Za-z0-9-]+/', '-', $label).'.png';

        $this->drawPlaceholder($path, $label, self::COLORS[$family] ?? [226, 232, 240]);

        $product->addMedia($path)->preservingOriginal()->toMediaCollection(Product::GALLERY);
    }

    /**
     * @param  array{0: int, 1: int, 2: int}  $rgb
     */
    private function drawPlaceholder(string $path, string $label, array $rgb): void
    {
        $small = imagecreatetruecolor(160, 160);
        imagefill($small, 0, 0, imagecolorallocate($small, ...$rgb));
        imagerectangle($small, 8, 8, 151, 151, imagecolorallocate($small, 148, 163, 184));

        $text = imagecolorallocate($small, 51, 65, 85);
        $width = imagefontwidth(3) * strlen($label);
        imagestring($small, 3, max(0, (int) ((160 - $width) / 2)), 74, $label, $text);

        $large = imagecreatetruecolor(800, 800);
        imagecopyresampled($large, $small, 0, 0, 0, 0, 800, 800, 160, 160);
        imagepng($large, $path);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function catalogue(): array
    {
        $tonerTiers = fn (float $price): array => [3 => round($price * 0.95, 2), 10 => round($price * 0.90, 2)];

        return [
            // Consommables & Toners
            ['sku' => 'CF380A', 'name' => 'Toner HP 312A noir (CF380A)', 'category' => 'Toners HP', 'brand' => 'HP', 'price' => 790, 'weight' => 780, 'featured' => true, 'compatible' => 'HP 312A', 'tiers' => $tonerTiers(790),
                'excerpt' => "Toner d'origine HP 312A noir pour imprimantes HP Color LaserJet Pro M476.",
                'specs' => [['Référence constructeur', 'CF380A'], ['Couleur', 'Noir'], ['Type', "Toner d'origine"]]],
            ['sku' => 'CF381A', 'name' => 'Toner HP 312A cyan (CF381A)', 'category' => 'Toners HP', 'brand' => 'HP', 'price' => 1090, 'weight' => 760, 'compatible' => 'HP 312A', 'tiers' => $tonerTiers(1090),
                'excerpt' => "Toner d'origine HP 312A cyan pour imprimantes HP Color LaserJet Pro M476.",
                'specs' => [['Référence constructeur', 'CF381A'], ['Couleur', 'Cyan'], ['Type', "Toner d'origine"]]],
            ['sku' => 'CF382A', 'name' => 'Toner HP 312A jaune (CF382A)', 'category' => 'Toners HP', 'brand' => 'HP', 'price' => 1090, 'weight' => 760, 'stock' => 3, 'compatible' => 'HP 312A', 'tiers' => $tonerTiers(1090),
                'excerpt' => "Toner d'origine HP 312A jaune pour imprimantes HP Color LaserJet Pro M476.",
                'specs' => [['Référence constructeur', 'CF382A'], ['Couleur', 'Jaune'], ['Type', "Toner d'origine"]]],
            ['sku' => 'CF383A', 'name' => 'Toner HP 312A magenta (CF383A)', 'category' => 'Toners HP', 'brand' => 'HP', 'price' => 1090, 'weight' => 760, 'stock' => 0, 'compatible' => 'HP 312A', 'tiers' => $tonerTiers(1090),
                'excerpt' => "Toner d'origine HP 312A magenta pour imprimantes HP Color LaserJet Pro M476.",
                'specs' => [['Référence constructeur', 'CF383A'], ['Couleur', 'Magenta'], ['Type', "Toner d'origine"]]],
            ['sku' => 'CE285A', 'name' => 'Toner HP 85A noir (CE285A)', 'category' => 'Toners HP', 'brand' => 'HP', 'price' => 540, 'weight' => 700, 'compatible' => 'HP 85A', 'tiers' => $tonerTiers(540),
                'excerpt' => "Toner d'origine HP 85A noir pour imprimantes HP LaserJet Pro P1102 et M1132.",
                'specs' => [['Référence constructeur', 'CE285A'], ['Couleur', 'Noir'], ['Type', "Toner d'origine"]]],
            ['sku' => '55B5000', 'name' => 'Toner Lexmark 55B5000 noir', 'category' => 'Toners Lexmark', 'brand' => 'Lexmark', 'price' => 1650, 'weight' => 900, 'featured' => true, 'compatible' => 'Lexmark', 'tiers' => $tonerTiers(1650),
                'excerpt' => "Toner d'origine Lexmark 55B5000 noir pour les séries MS331, MS431, MX331 et MX431.",
                'specs' => [['Référence constructeur', '55B5000'], ['Couleur', 'Noir'], ['Type', "Toner d'origine"]]],
            ['sku' => 'CRG-725', 'name' => 'Cartouche Canon 725 noir', 'category' => 'Toners Canon', 'brand' => 'Canon', 'price' => 480, 'weight' => 650, 'compatible' => 'Canon', 'tiers' => $tonerTiers(480),
                'excerpt' => "Cartouche d'origine Canon 725 noir pour imprimantes i-SENSYS LBP6000 et MF3010.",
                'specs' => [['Référence constructeur', 'CRG-725'], ['Couleur', 'Noir'], ['Type', "Cartouche d'origine"]]],
            ['sku' => 'TN-2420', 'name' => 'Toner Brother TN-2420 noir', 'category' => 'Toners Brother', 'brand' => 'Brother', 'price' => 720, 'weight' => 720, 'compatible' => 'TN-2420', 'tiers' => $tonerTiers(720),
                'excerpt' => "Toner d'origine Brother TN-2420 noir pour imprimantes laser monochromes Brother.",
                'specs' => [['Référence constructeur', 'TN-2420'], ['Couleur', 'Noir'], ['Type', "Toner d'origine"]]],
            ['sku' => 'TZE-231', 'name' => 'Ruban Brother TZe-231 noir sur blanc 12 mm', 'category' => 'Rubans pour étiqueteuses', 'brand' => 'Brother', 'price' => 165, 'weight' => 40, 'compatible' => 'TZe 12mm', 'tiers' => [5 => 150, 20 => 135],
                'excerpt' => 'Ruban laminé Brother TZe-231, texte noir sur fond blanc, largeur 12 mm.',
                'specs' => [['Largeur', '12 mm'], ['Texte / fond', 'Noir sur blanc'], ['Type', 'Ruban laminé TZe']]],
            ['sku' => 'TZE-551', 'name' => 'Ruban Brother TZe-551 noir sur vert 24 mm', 'category' => 'Rubans pour étiqueteuses', 'brand' => 'Brother', 'price' => 245, 'weight' => 60, 'featured' => true, 'compatible' => 'TZe 24mm', 'tiers' => [5 => 225, 20 => 205],
                'excerpt' => 'Ruban laminé Brother TZe-551, texte noir sur fond vert, largeur 24 mm.',
                'specs' => [['Largeur', '24 mm'], ['Texte / fond', 'Noir sur vert'], ['Type', 'Ruban laminé TZe']]],
            ['sku' => 'TZE-651', 'name' => 'Ruban Brother TZe-651 noir sur jaune 24 mm', 'category' => 'Rubans pour étiqueteuses', 'brand' => 'Brother', 'price' => 245, 'weight' => 60, 'compatible' => 'TZe 24mm', 'tiers' => [5 => 225, 20 => 205],
                'excerpt' => 'Ruban laminé Brother TZe-651, texte noir sur fond jaune, largeur 24 mm.',
                'specs' => [['Largeur', '24 mm'], ['Texte / fond', 'Noir sur jaune'], ['Type', 'Ruban laminé TZe']]],

            // Équipements informatiques & Accessoires
            ['sku' => 'GWN7803P', 'name' => 'Switch réseau PoE Grandstream GWN7803P', 'category' => 'Réseau et switchs PoE', 'brand' => 'Grandstream', 'price' => 2390, 'weight' => 1800, 'stock' => 8, 'featured' => true,
                'excerpt' => 'Switch réseau Gigabit PoE administrable Grandstream GWN7803P.',
                'specs' => [['Type', 'Switch administrable PoE'], ['Ports', 'Gigabit RJ45 PoE et ports SFP']]],
            ['sku' => 'RJ45-C6-5M', 'name' => 'Câble réseau RJ45 Cat6 5 m', 'category' => 'Réseau et switchs PoE', 'price' => 35, 'weight' => 120, 'tiers' => [10 => 30, 50 => 26],
                'excerpt' => 'Câble réseau RJ45 catégorie 6, longueur 5 m.',
                'specs' => [['Catégorie', '6'], ['Longueur', '5 m']]],
            ['sku' => 'WACOM-INTUOS-S', 'name' => 'Tablette graphique Wacom Intuos S', 'category' => 'Tablettes graphiques', 'brand' => 'Wacom', 'price' => 790, 'weight' => 500, 'stock' => 12,
                'excerpt' => 'Tablette graphique Wacom Intuos, format S, avec stylet.',
                'specs' => [['Format', 'S (petit)'], ['Stylet', 'Inclus']]],
            ['sku' => 'WACOM-INTUOS-M', 'name' => 'Tablette graphique Wacom Intuos M', 'category' => 'Tablettes graphiques', 'brand' => 'Wacom', 'price' => 1190, 'weight' => 800, 'stock' => 5,
                'excerpt' => 'Tablette graphique Wacom Intuos, format M, avec stylet.',
                'specs' => [['Format', 'M (moyen)'], ['Stylet', 'Inclus']]],
            ['sku' => 'DELL-KM636', 'name' => 'Clavier et souris sans fil Dell KM636', 'category' => 'Claviers et souris', 'brand' => 'Dell', 'price' => 450, 'weight' => 900, 'featured' => true, 'tiers' => [5 => 420, 20 => 390],
                'excerpt' => 'Ensemble clavier et souris sans fil Dell KM636.',
                'specs' => [['Connexion', 'Sans fil'], ['Contenu', 'Clavier + souris']]],
            ['sku' => 'DELL-KB216', 'name' => 'Clavier filaire Dell KB216', 'category' => 'Claviers et souris', 'brand' => 'Dell', 'price' => 190, 'weight' => 600, 'tiers' => [10 => 175, 50 => 160],
                'excerpt' => 'Clavier USB filaire Dell KB216.',
                'specs' => [['Connexion', 'USB filaire']]],
            ['sku' => 'DELL-MS116', 'name' => 'Souris optique filaire Dell MS116', 'category' => 'Claviers et souris', 'brand' => 'Dell', 'price' => 70, 'weight' => 120, 'tiers' => [10 => 62, 50 => 55],
                'excerpt' => 'Souris optique USB filaire Dell MS116.',
                'specs' => [['Connexion', 'USB filaire'], ['Capteur', 'Optique']]],

            // Matériel de bureau & Papeterie
            ['sku' => 'PT-E110VP', 'name' => 'Étiqueteuse professionnelle Brother PT-E110VP', 'category' => 'Étiqueteuses professionnelles', 'brand' => 'Brother', 'price' => 1290, 'weight' => 900, 'featured' => true, 'stock' => 10,
                'excerpt' => 'Étiqueteuse portable Brother P-touch PT-E110VP, compatible rubans TZe.',
                'specs' => [['Type', 'Étiqueteuse portable'], ['Rubans', 'Brother TZe']]],
            ['sku' => 'SET-CUIR-7', 'name' => 'Ensemble de bureau simili-cuir 7 pièces', 'category' => 'Ensembles de bureau', 'price' => 290, 'weight' => 1800, 'tiers' => [5 => 270, 20 => 245],
                'excerpt' => 'Ensemble de bureau en simili-cuir, 7 pièces.',
                'specs' => [['Matière', 'Simili-cuir'], ['Nombre de pièces', '7']]],
            ['sku' => 'SET-CUIR-9', 'name' => 'Ensemble de bureau simili-cuir 9 pièces', 'category' => 'Ensembles de bureau', 'price' => 390, 'weight' => 2400, 'tiers' => [5 => 365, 20 => 330],
                'excerpt' => 'Ensemble de bureau en simili-cuir, 9 pièces.',
                'specs' => [['Matière', 'Simili-cuir'], ['Nombre de pièces', '9']]],
            ['sku' => 'BLOC-A5-10', 'name' => 'Blocs-notes A5 (lot de 10)', 'category' => 'Blocs-notes et carnets', 'price' => 55, 'weight' => 900, 'tiers' => [10 => 48, 50 => 42],
                'excerpt' => 'Lot de 10 blocs-notes format A5.',
                'specs' => [['Format', 'A5'], ['Conditionnement', 'Lot de 10']]],
            ['sku' => 'CARNET-A4', 'name' => 'Carnet à spirale A4', 'category' => 'Blocs-notes et carnets', 'price' => 18, 'weight' => 300, 'tiers' => [20 => 15, 100 => 12],
                'excerpt' => 'Carnet à spirale format A4.',
                'specs' => [['Format', 'A4'], ['Reliure', 'Spirale']]],
            ['sku' => 'STYLO-GEL-12', 'name' => 'Stylos gel (boîte de 12)', 'category' => 'Stylos et surligneurs', 'price' => 48, 'weight' => 150, 'tiers' => [10 => 42, 50 => 37],
                'excerpt' => 'Boîte de 12 stylos gel.',
                'specs' => [['Type', 'Stylo gel'], ['Conditionnement', 'Boîte de 12']]],
            ['sku' => 'STYLO-PLUME', 'name' => 'Stylo plume', 'category' => 'Stylos et surligneurs', 'price' => 65, 'weight' => 40,
                'excerpt' => 'Stylo plume pour le bureau.',
                'specs' => [['Type', 'Stylo plume']]],
            ['sku' => 'SURLIGN-4', 'name' => 'Surligneurs (pack de 4)', 'category' => 'Stylos et surligneurs', 'price' => 22, 'weight' => 100, 'tiers' => [10 => 19, 50 => 16],
                'excerpt' => 'Pack de 4 surligneurs de couleurs assorties.',
                'specs' => [['Conditionnement', 'Pack de 4']]],

            // Mobilier & Organisation du travail
            ['sku' => 'CHAISE-ERGO', 'name' => 'Chaise de bureau ergonomique', 'category' => 'Chaises ergonomiques', 'price' => 1490, 'weight' => 12000, 'stock' => 6, 'featured' => true, 'tiers' => [5 => 1390, 10 => 1290],
                'excerpt' => 'Chaise de bureau ergonomique réglable.',
                'specs' => [['Type', 'Chaise ergonomique'], ['Réglages', 'Hauteur']]],
            ['sku' => 'BUREAU-140', 'name' => 'Bureau droit 140 cm', 'category' => 'Bureaux et coworking', 'price' => 1290, 'weight' => 28000, 'stock' => 4, 'threshold' => 2,
                'excerpt' => 'Bureau droit de 140 cm de large.',
                'specs' => [['Largeur', '140 cm']]],
            ['sku' => 'TABLE-COWORK', 'name' => 'Table de coworking', 'category' => 'Bureaux et coworking', 'price' => 1890, 'weight' => 35000, 'stock' => 3, 'threshold' => 2,
                'excerpt' => 'Grande table pour espaces de coworking.',
                'specs' => [['Usage', 'Coworking']]],
            ['sku' => 'PORTE-DOC', 'name' => 'Porte-documents', 'category' => 'Rangement et archivage', 'price' => 35, 'weight' => 350, 'tiers' => [10 => 30, 50 => 26],
                'excerpt' => 'Porte-documents pour organiser vos dossiers.',
                'specs' => [['Usage', 'Rangement de documents']]],
            ['sku' => 'CLASSEUR-10', 'name' => 'Classeurs à levier A4 (lot de 10)', 'category' => 'Rangement et archivage', 'price' => 120, 'weight' => 3500, 'tiers' => [5 => 110, 20 => 98],
                'excerpt' => 'Lot de 10 classeurs à levier format A4.',
                'specs' => [['Format', 'A4'], ['Conditionnement', 'Lot de 10']]],
            ['sku' => 'ARCHIVE-10', 'name' => "Boîtes d'archivage (lot de 10)", 'category' => 'Rangement et archivage', 'price' => 180, 'weight' => 4200, 'tiers' => [5 => 165, 20 => 150],
                'excerpt' => "Lot de 10 boîtes d'archivage.",
                'specs' => [['Conditionnement', 'Lot de 10']]],
            ['sku' => 'CORBEILLE-3', 'name' => 'Corbeille à courrier 3 niveaux', 'category' => 'Rangement et archivage', 'price' => 95, 'weight' => 1200, 'tiers' => [10 => 85, 50 => 76],
                'excerpt' => 'Corbeille à courrier à 3 niveaux.',
                'specs' => [['Niveaux', '3']]],

            // Autres consommables & Hygiène
            ['sku' => 'AGRAFES-24-6', 'name' => 'Agrafes 24/6 (boîte)', 'category' => 'Fournitures bureautiques', 'price' => 9, 'weight' => 100, 'tiers' => [20 => 7.5, 100 => 6.5],
                'excerpt' => 'Boîte d\'agrafes 24/6.',
                'specs' => [['Format', '24/6']]],
            ['sku' => 'TROMBONES-100', 'name' => 'Trombones (boîte de 100)', 'category' => 'Fournitures bureautiques', 'price' => 6, 'weight' => 120, 'tiers' => [20 => 5, 100 => 4.2],
                'excerpt' => 'Boîte de 100 trombones.',
                'specs' => [['Conditionnement', 'Boîte de 100']]],
            ['sku' => 'RUBAN-ADH-6', 'name' => 'Ruban adhésif transparent (lot de 6)', 'category' => 'Fournitures bureautiques', 'price' => 24, 'weight' => 400, 'tiers' => [10 => 21, 50 => 18],
                'excerpt' => 'Lot de 6 rouleaux de ruban adhésif transparent.',
                'specs' => [['Conditionnement', 'Lot de 6']]],
            ['sku' => 'SAC-POUBELLE-100', 'name' => 'Sacs poubelle 100 L (rouleau de 20)', 'category' => 'Hygiène et entretien', 'price' => 32, 'weight' => 700, 'stock' => 60, 'tiers' => [10 => 28, 50 => 25],
                'excerpt' => 'Rouleau de 20 sacs poubelle de 100 litres.',
                'specs' => [['Contenance', '100 L'], ['Conditionnement', 'Rouleau de 20']]],
            ['sku' => 'DESINFECTANT-5L', 'name' => 'Désinfectant surfaces 5 L', 'category' => 'Hygiène et entretien', 'price' => 95, 'weight' => 5200, 'tiers' => [5 => 88, 20 => 80],
                'excerpt' => 'Bidon de désinfectant pour surfaces, 5 litres.',
                'specs' => [['Contenance', '5 L']]],
            ['sku' => 'GANTS-NITRILE-100', 'name' => 'Gants nitrile (boîte de 100)', 'category' => 'Hygiène et entretien', 'price' => 79, 'weight' => 900, 'stock' => 4, 'tiers' => [10 => 70, 50 => 62],
                'excerpt' => 'Boîte de 100 gants en nitrile.',
                'specs' => [['Matière', 'Nitrile'], ['Conditionnement', 'Boîte de 100']]],
            ['sku' => 'FILM-BULLE-50', 'name' => 'Film bulle (rouleau de 50 m)', 'category' => 'Emballage', 'price' => 85, 'weight' => 1500, 'tiers' => [5 => 78, 20 => 70],
                'excerpt' => 'Rouleau de film bulle de 50 mètres.',
                'specs' => [['Longueur', '50 m']]],
            ['sku' => 'CARTON-10', 'name' => "Cartons d'emballage (lot de 10)", 'category' => 'Emballage', 'price' => 60, 'weight' => 3000, 'tiers' => [10 => 52, 50 => 46],
                'excerpt' => "Lot de 10 cartons d'emballage.",
                'specs' => [['Conditionnement', 'Lot de 10']]],
        ];
    }
}
