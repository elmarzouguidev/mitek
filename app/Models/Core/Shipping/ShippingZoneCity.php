<?php

namespace App\Models\Core\Shipping;

use App\Services\Core\Shipping\DeliveryEstimator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class ShippingZoneCity extends Model
{
    /**
     * Cities are matched case- and accent-insensitively ("Fès" = "fes").
     */
    public static function normalize(string $city): string
    {
        return Str::of($city)->ascii()->lower()->squish()->toString();
    }

    protected static function booted(): void
    {
        static::saving(fn (self $city) => $city->normalized = self::normalize($city->city));
        static::saved(fn () => DeliveryEstimator::flushCities());
        static::deleted(fn () => DeliveryEstimator::flushCities());
    }

    /**
     * @return BelongsTo<ShippingZone, $this>
     */
    public function zone(): BelongsTo
    {
        return $this->belongsTo(ShippingZone::class, 'shipping_zone_id');
    }
}
