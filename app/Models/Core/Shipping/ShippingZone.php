<?php

namespace App\Models\Core\Shipping;

use App\Policies\ShippingZonePolicy;
use App\Services\Core\Shipping\DeliveryEstimator;
use Database\Factories\Core\Shipping\ShippingZoneFactory;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[UsePolicy(ShippingZonePolicy::class)]
class ShippingZone extends Model
{
    /** @use HasFactory<ShippingZoneFactory> */
    use HasFactory;

    protected static function booted(): void
    {
        static::saved(fn () => DeliveryEstimator::flushCities());
        static::deleted(fn () => DeliveryEstimator::flushCities());
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return HasMany<ShippingZoneCity, $this>
     */
    public function cities(): HasMany
    {
        return $this->hasMany(ShippingZoneCity::class)->orderBy('city');
    }

    /**
     * @return HasMany<ShippingRate, $this>
     */
    public function rates(): HasMany
    {
        return $this->hasMany(ShippingRate::class)->orderBy('min_weight_g');
    }

    /**
     * @param  Builder<ShippingZone>  $query
     * @return Builder<ShippingZone>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
