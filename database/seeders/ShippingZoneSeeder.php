<?php

namespace Database\Seeders;

use App\Models\Core\Shipping\ShippingZone;
use App\Support\Money;
use Illuminate\Database\Seeder;

/**
 * DEMO shipping grid: zones, cities and prices (HT, MAD) are placeholders, not Mitek's real rates.
 * Replace them from the back office (Livraison) before going live.
 */
class ShippingZoneSeeder extends Seeder
{
    /**
     * @var array<int, array{name: string, default: bool, cities: array<int, string>, prices: array<int, int>, transit: array{0: int, 1: int}}>
     */
    private const ZONES = [
        ['name' => 'Casablanca et environs', 'default' => false, 'cities' => ['Casablanca', 'Mohammedia', 'Berrechid', 'Settat'], 'prices' => [25, 40, 70, 110], 'transit' => [1, 2]],
        ['name' => 'Grandes villes', 'default' => false, 'cities' => ['Rabat', 'Salé', 'Témara', 'Kénitra', 'Marrakech', 'Fès', 'Meknès', 'Tanger', 'Tétouan', 'Agadir', 'Oujda', 'El Jadida'], 'prices' => [35, 55, 90, 140], 'transit' => [1, 3]],
        ['name' => 'Reste du Maroc', 'default' => true, 'cities' => [], 'prices' => [45, 70, 110, 170], 'transit' => [2, 4]],
    ];

    /** Weight brackets in grams: [min, max or null]. */
    private const BRACKETS = [[0, 5000], [5001, 15000], [15001, 30000], [30001, null]];

    public function run(): void
    {
        foreach (self::ZONES as $definition) {
            $zone = ShippingZone::firstOrCreate(['name' => $definition['name']], ['is_default' => $definition['default'], 'transit_min_days' => $definition['transit'][0], 'transit_max_days' => $definition['transit'][1]]);

            if ($zone->rates()->exists()) {
                continue;
            }

            foreach ($definition['cities'] as $city) {
                $zone->cities()->create(['city' => $city]);
            }

            foreach (self::BRACKETS as $index => [$min, $max]) {
                $zone->rates()->create([
                    'min_weight_g' => $min,
                    'max_weight_g' => $max,
                    'price_ht' => Money::fromDecimal($definition['prices'][$index]),
                ]);
            }
        }
    }
}
