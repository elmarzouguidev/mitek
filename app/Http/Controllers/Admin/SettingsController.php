<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateAnnouncementSettingsRequest;
use App\Http\Requests\Admin\UpdateCatalogSettingsRequest;
use App\Http\Requests\Admin\UpdateClaimsSettingsRequest;
use App\Http\Requests\Admin\UpdateGeneralSettingsRequest;
use App\Http\Requests\Admin\UpdatePaymentSettingsRequest;
use App\Http\Requests\Admin\UpdateShippingSettingsRequest;
use App\Settings\AnnouncementSettings;
use App\Settings\CatalogSettings;
use App\Settings\ClaimsSettings;
use App\Settings\GeneralSettings;
use App\Settings\PaymentSettings;
use App\Settings\ShippingSettings;
use App\Support\Money;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class SettingsController extends Controller
{
    public function edit(GeneralSettings $general, PaymentSettings $payment, ShippingSettings $shipping, AnnouncementSettings $announcement, ClaimsSettings $claims, CatalogSettings $catalog): Response
    {
        return Inertia::render('Settings/Edit', [
            'general' => $general->toArray(),
            'payment' => [
                ...$payment->toArray(),
                'cod_limit' => Money::fromCents($payment->cod_limit_cents)->toDecimal(),
            ],
            'shipping' => [
                ...$shipping->toArray(),
                'own_carrier_fee' => Money::fromCents($shipping->own_carrier_fee_cents)->toDecimal(),
            ],
            'announcement' => $announcement->toArray(),
            'claims' => [
                'values' => $claims->toArray(),
                'descriptions' => ClaimsSettings::descriptions(),
            ],
            'catalog' => $catalog->toArray(),
        ]);
    }

    public function updateGeneral(UpdateGeneralSettingsRequest $request, GeneralSettings $settings): RedirectResponse
    {
        $settings->fill($request->validated())->save();

        return back()->with('success', __('app.admin.saved'));
    }

    public function updatePayment(UpdatePaymentSettingsRequest $request, PaymentSettings $settings): RedirectResponse
    {
        $data = $request->validated();
        $data['cod_limit_cents'] = Money::fromDecimal($data['cod_limit'])->cents;
        unset($data['cod_limit']);

        $settings->fill($data)->save();

        return back()->with('success', __('app.admin.saved'));
    }

    public function updateShipping(UpdateShippingSettingsRequest $request, ShippingSettings $settings): RedirectResponse
    {
        $data = $request->validated();
        $data['own_carrier_fee_cents'] = Money::fromDecimal($data['own_carrier_fee'])->cents;
        unset($data['own_carrier_fee']);
        $data['working_days'] = collect($data['working_days'])->map(fn ($day): int => (int) $day)->sort()->values()->all();
        $data['holidays'] = collect($data['holidays'] ?? [])->sort()->values()->all();

        $settings->fill($data)->save();

        return back()->with('success', __('app.admin.saved'));
    }

    public function updateAnnouncement(UpdateAnnouncementSettingsRequest $request, AnnouncementSettings $settings): RedirectResponse
    {
        $settings->fill($request->validated())->save();

        return back()->with('success', __('app.admin.saved'));
    }

    public function updateClaims(UpdateClaimsSettingsRequest $request, ClaimsSettings $settings): RedirectResponse
    {
        $settings->fill($request->validated())->save();

        return back()->with('success', __('app.admin.saved'));
    }

    public function updateCatalog(UpdateCatalogSettingsRequest $request, CatalogSettings $settings): RedirectResponse
    {
        $settings->fill($request->validated())->save();

        return back()->with('success', __('app.admin.saved'));
    }
}
