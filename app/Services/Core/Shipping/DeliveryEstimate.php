<?php

namespace App\Services\Core\Shipping;

use App\Models\Core\Shipping\ShippingZone;
use App\Support\Money;
use Carbon\CarbonImmutable;

/**
 * Delivery window for a city: when the parcel is expected and what the shipping costs.
 */
final readonly class DeliveryEstimate
{
    public function __construct(
        public string $city,
        public ShippingZone $zone,
        public bool $usedDefaultZone,
        public int $dispatchDays,
        public int $transitMinDays,
        public int $transitMaxDays,
        public CarbonImmutable $earliest,
        public CarbonImmutable $latest,
        public ?Money $shippingTtc,
    ) {}

    /**
     * "jeu. 27 nov. – lun. 1 déc." (or a single date when both ends are the same day).
     */
    public function rangeLabel(): string
    {
        $earliest = $this->earliest->isoFormat('ddd D MMM');

        return $this->earliest->isSameDay($this->latest) ? $earliest : $earliest.' – '.$this->latest->isoFormat('ddd D MMM');
    }

    /**
     * The sentences shown to the customer, so the page and the JSON endpoint print exactly the same text.
     *
     * @return array{estimate: string, transit: string, fee: string, notice: string|null, hint: string}
     */
    public function lines(): array
    {
        return [
            'estimate' => __('delivery.estimate', ['range' => $this->rangeLabel(), 'city' => $this->city]),
            'transit' => __('delivery.transit_line', ['transit' => $this->transitText()]),
            'fee' => $this->shippingTtc === null ? __('delivery.fee_unknown') : __('delivery.fee', ['fee' => $this->shippingTtc->format()]),
            'notice' => $this->usedDefaultZone ? __('delivery.default_zone') : null,
            'hint' => __('delivery.hint'),
        ];
    }

    public function transitText(): string
    {
        return $this->transitMinDays === $this->transitMaxDays
            ? trans_choice('delivery.transit_single', $this->transitMaxDays, ['n' => $this->transitMaxDays])
            : __('delivery.transit_range', ['min' => $this->transitMinDays, 'max' => $this->transitMaxDays]);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'city' => $this->city,
            'zone' => $this->zone->name,
            'default_zone' => $this->usedDefaultZone,
            'range' => $this->rangeLabel(),
            'earliest' => $this->earliest->toDateString(),
            'latest' => $this->latest->toDateString(),
            'transit' => $this->transitText(),
            'fee' => $this->shippingTtc?->format(),
            'lines' => $this->lines(),
        ];
    }
}
