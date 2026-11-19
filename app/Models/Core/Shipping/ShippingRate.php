<?php

namespace App\Models\Core\Shipping;

use App\Casts\MoneyCast;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShippingRate extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'min_weight_g' => 'integer',
            'max_weight_g' => 'integer',
            'price_ht' => MoneyCast::class,
        ];
    }

    /**
     * Weight brackets are inclusive on both ends; no upper bound means "and above".
     */
    public function covers(int $weightGrams): bool
    {
        return $weightGrams >= $this->min_weight_g
            && ($this->max_weight_g === null || $weightGrams <= $this->max_weight_g);
    }

    /**
     * @return BelongsTo<ShippingZone, $this>
     */
    public function zone(): BelongsTo
    {
        return $this->belongsTo(ShippingZone::class, 'shipping_zone_id');
    }
}
