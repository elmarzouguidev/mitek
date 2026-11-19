<?php

namespace App\Services\Core\Shipping;

use App\Http\Requests\Admin\ShippingZoneRequest;
use App\Models\Core\Shipping\ShippingZone;
use App\Support\Money;
use Illuminate\Support\Facades\DB;

class ShippingZoneWriter
{
    /**
     * @param  array<string, mixed>  $data  validated {@see ShippingZoneRequest} input
     */
    public function save(ShippingZone $zone, array $data): ShippingZone
    {
        return DB::transaction(function () use ($zone, $data): ShippingZone {
            $isDefault = (bool) ($data['is_default'] ?? false);

            if ($isDefault) {
                ShippingZone::query()->when($zone->exists, fn ($query) => $query->whereKeyNot($zone->id))->update(['is_default' => false]);
            }

            $zone->fill([
                'name' => $data['name'],
                'is_default' => $isDefault,
                'is_active' => (bool) ($data['is_active'] ?? false),
                'transit_min_days' => (int) $data['transit_min_days'],
                'transit_max_days' => (int) $data['transit_max_days'],
            ])->save();

            $zone->cities()->delete();
            foreach (array_unique(array_map('trim', $data['cities'] ?? [])) as $city) {
                if ($city !== '') {
                    $zone->cities()->create(['city' => $city]);
                }
            }

            $zone->rates()->delete();
            foreach ($data['rates'] as $rate) {
                $zone->rates()->create([
                    'min_weight_g' => (int) $rate['min_weight_g'],
                    'max_weight_g' => filled($rate['max_weight_g'] ?? null) ? (int) $rate['max_weight_g'] : null,
                    'price_ht' => Money::fromDecimal($rate['price_ht']),
                ]);
            }

            return $zone;
        });
    }
}
