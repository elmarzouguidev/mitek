<?php

namespace App\Http\Requests\Admin;

use App\Models\Core\Shipping\ShippingZone;
use App\Models\Core\Shipping\ShippingZoneCity;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class ShippingZoneRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isAdmin();
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'is_default' => ['boolean'],
            'is_active' => ['boolean'],
            'transit_min_days' => ['required', 'integer', 'between:0,30'],
            'transit_max_days' => ['required', 'integer', 'gte:transit_min_days', 'max:60'],
            'cities' => ['nullable', 'array', 'max:200'],
            'cities.*' => ['string', 'max:100'],
            'rates' => ['required', 'array', 'min:1', 'max:20'],
            'rates.*.min_weight_g' => ['required', 'integer', 'min:0', 'max:1000000000'],
            'rates.*.max_weight_g' => ['nullable', 'integer', 'gte:rates.*.min_weight_g', 'max:1000000000'],
            'rates.*.price_ht' => ['required', 'numeric', 'min:0', 'max:1000000'],
        ];
    }

    /**
     * A city belongs to one zone only, and weight brackets must not overlap.
     *
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $this->checkCities($validator);
                $this->checkRates($validator);
            },
        ];
    }

    private function checkCities(Validator $validator): void
    {
        $zone = $this->route('zone');
        $seen = [];

        foreach ((array) $this->input('cities', []) as $index => $city) {
            $normalized = ShippingZoneCity::normalize((string) $city);

            if (isset($seen[$normalized])) {
                $validator->errors()->add("cities.{$index}", __('validation.custom.city_duplicate', ['city' => $city]));

                continue;
            }

            $seen[$normalized] = true;

            $takenElsewhere = ShippingZoneCity::query()
                ->where('normalized', $normalized)
                ->when($zone instanceof ShippingZone, fn ($query) => $query->where('shipping_zone_id', '!=', $zone->id))
                ->exists();

            if ($takenElsewhere) {
                $validator->errors()->add("cities.{$index}", __('validation.custom.city_taken', ['city' => $city]));
            }
        }
    }

    private function checkRates(Validator $validator): void
    {
        $rates = collect((array) $this->input('rates', []))
            ->filter(fn (mixed $rate): bool => is_array($rate) && is_numeric($rate['min_weight_g'] ?? null))
            ->sortBy(fn (array $rate): int => (int) $rate['min_weight_g']);

        $previousMax = null;
        $previousOpen = false;

        foreach ($rates as $index => $rate) {
            $min = (int) $rate['min_weight_g'];

            if ($previousOpen || ($previousMax !== null && $min <= $previousMax)) {
                $validator->errors()->add("rates.{$index}.min_weight_g", __('validation.custom.rate_overlap'));
            }

            $previousOpen = ! is_numeric($rate['max_weight_g'] ?? null);
            $previousMax = $previousOpen ? null : (int) $rate['max_weight_g'];
        }
    }
}
