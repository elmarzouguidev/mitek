<?php

namespace App\Services\Core\Shipping;

use App\Enums\CarrierMode;
use App\Exceptions\ShippingUnavailable;
use App\Models\Core\Shipping\ShippingZone;
use App\Models\Core\Shipping\ShippingZoneCity;
use App\Settings\GeneralSettings;
use App\Settings\ShippingSettings;
use App\Support\Money;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Cache;

/**
 * Delivery estimate = clock start (payment validation, or order confirmation for cash on delivery)
 * + dispatch ({@see ShippingSettings::$dispatch_days}) + transit (the zone's min–max days), all counted
 * in working days ({@see ShippingSettings::$working_days} minus {@see ShippingSettings::$holidays}).
 */
class DeliveryEstimator
{
    public const CITIES_CACHE_KEY = 'shipping.cities';

    public function __construct(
        private readonly ShippingSettings $settings,
        private readonly GeneralSettings $general,
        private readonly ShippingCalculator $calculator,
    ) {}

    /**
     * @throws ShippingUnavailable when no active zone exists for the city and there is no default zone
     */
    public function estimate(string $city, int $weightGrams = 0, ?CarbonInterface $from = null): DeliveryEstimate
    {
        $zone = $this->calculator->zoneFor($city) ?? throw ShippingUnavailable::noZone();
        $start = CarbonImmutable::instance($from ?? now());
        $mapped = ShippingZoneCity::query()->where('normalized', ShippingZoneCity::normalize($city))->where('shipping_zone_id', $zone->id)->exists();

        return new DeliveryEstimate(
            city: $city,
            zone: $zone,
            usedDefaultZone: ! $mapped,
            dispatchDays: $this->settings->dispatch_days,
            transitMinDays: $zone->transit_min_days,
            transitMaxDays: $zone->transit_max_days,
            earliest: $this->addWorkingDays($start, $this->settings->dispatch_days + $zone->transit_min_days),
            latest: $this->addWorkingDays($start, $this->settings->dispatch_days + $zone->transit_max_days),
            shippingTtc: $this->shippingTtc($city, $weightGrams),
        );
    }

    /**
     * Moves forward by working days; zero rolls a non-working day over to the next working one.
     */
    public function addWorkingDays(CarbonInterface $date, int $days): CarbonImmutable
    {
        $current = CarbonImmutable::instance($date)->startOfDay();
        $remaining = max(0, $days);
        $guard = 0;

        while (($remaining > 0 || ! $this->isWorkingDay($current)) && $guard++ < 800) {
            if ($remaining > 0) {
                $current = $current->addDay();
                if ($this->isWorkingDay($current)) {
                    $remaining--;
                }
            } else {
                $current = $current->addDay();
            }
        }

        return $current;
    }

    public function isWorkingDay(CarbonInterface $date): bool
    {
        return in_array($date->dayOfWeekIso, $this->settings->working_days, true)
            && ! in_array($date->toDateString(), $this->settings->holidays, true);
    }

    /**
     * "sous 48 h" (dispatch window), or "le jour même" when orders leave the same day.
     */
    public function dispatchText(): string
    {
        return $this->settings->dispatch_days > 0
            ? __('delivery.dispatch_within', ['hours' => $this->settings->dispatch_days * 24])
            : __('delivery.dispatch_same_day');
    }

    /**
     * The wording used on the delivery page and in the FAQ ("24 à 72 h selon la ville"), derived from the
     * transit days of the active zones so it cannot contradict the estimator. Falls back to the text from
     * the settings while no zone is active.
     */
    public function summaryText(): string
    {
        $zones = ShippingZone::query()->active()->get(['transit_min_days', 'transit_max_days']);

        if ($zones->isEmpty()) {
            return $this->settings->delivery_estimate;
        }

        $min = max(1, (int) $zones->min('transit_min_days')) * 24;
        $max = max(1, (int) $zones->max('transit_max_days')) * 24;

        return $min === $max
            ? __('delivery.summary_single', ['hours' => $min])
            : __('delivery.summary_range', ['min' => $min, 'max' => $max]);
    }

    /**
     * City names offered as suggestions (every mapped city of the active zones).
     *
     * @return array<int, string>
     */
    public function cities(): array
    {
        return Cache::remember(self::CITIES_CACHE_KEY, now()->addDay(), fn (): array => ShippingZoneCity::query()
            ->whereHas('zone', fn ($zone) => $zone->active())
            ->orderBy('city')
            ->pluck('city')
            ->all());
    }

    public static function flushCities(): void
    {
        Cache::forget(self::CITIES_CACHE_KEY);
    }

    private function shippingTtc(string $city, int $weightGrams): ?Money
    {
        try {
            $quote = $this->calculator->quote($city, $weightGrams, CarrierMode::Mitek);
        } catch (ShippingUnavailable) {
            return null;
        }

        return $quote->priceHt->plus($quote->priceHt->percentage($this->general->vat_rate));
    }
}
