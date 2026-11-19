<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ShippingZoneRequest;
use App\Models\Core\Shipping\ShippingRate;
use App\Models\Core\Shipping\ShippingZone;
use App\Models\Core\Shipping\ShippingZoneCity;
use App\Services\Core\Shipping\ShippingZoneWriter;
use App\Support\MoroccanCities;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class ShippingZoneController extends Controller
{
    public function index(): Response
    {
        Gate::authorize('viewAny', ShippingZone::class);

        return Inertia::render('Shipping/Index', [
            'zones' => ShippingZone::query()->withCount(['cities', 'rates'])->orderByDesc('is_default')->orderBy('name')->get()
                ->map(fn (ShippingZone $zone): array => [
                    'id' => $zone->id,
                    'name' => $zone->name,
                    'is_default' => $zone->is_default,
                    'is_active' => $zone->is_active,
                    'transit' => $zone->transit_min_days.'–'.$zone->transit_max_days.' j',
                    'cities_count' => $zone->cities_count,
                    'rates_count' => $zone->rates_count,
                ])->values(),
        ]);
    }

    public function create(): Response
    {
        Gate::authorize('create', ShippingZone::class);

        return Inertia::render('Shipping/Form', ['zone' => null, 'citySuggestions' => MoroccanCities::all()]);
    }

    public function store(ShippingZoneRequest $request, ShippingZoneWriter $writer): RedirectResponse
    {
        $writer->save(new ShippingZone, $request->validated());

        return redirect()->route('admin.shipping.index')->with('success', __('admin.shipping.saved'));
    }

    public function edit(ShippingZone $zone): Response
    {
        Gate::authorize('update', $zone);

        $zone->load(['cities', 'rates']);

        return Inertia::render('Shipping/Form', [
            'zone' => [
                'id' => $zone->id,
                'name' => $zone->name,
                'is_default' => $zone->is_default,
                'is_active' => $zone->is_active,
                'transit_min_days' => $zone->transit_min_days,
                'transit_max_days' => $zone->transit_max_days,
                'cities' => $zone->cities->map(fn (ShippingZoneCity $city): string => $city->city)->values(),
                'rates' => $zone->rates->map(fn (ShippingRate $rate): array => [
                    'min_weight_g' => $rate->min_weight_g,
                    'max_weight_g' => $rate->max_weight_g,
                    'price_ht' => $rate->price_ht->toDecimal(),
                ])->values(),
            ],
            'citySuggestions' => MoroccanCities::all(),
        ]);
    }

    public function update(ShippingZoneRequest $request, ShippingZone $zone, ShippingZoneWriter $writer): RedirectResponse
    {
        $writer->save($zone, $request->validated());

        return redirect()->route('admin.shipping.index')->with('success', __('admin.shipping.saved'));
    }

    public function destroy(ShippingZone $zone): RedirectResponse
    {
        Gate::authorize('delete', $zone);

        $zone->delete();

        return redirect()->route('admin.shipping.index')->with('success', __('admin.shipping.deleted'));
    }
}
