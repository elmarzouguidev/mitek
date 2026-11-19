<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateGeneralSettingsRequest;
use App\Http\Requests\Admin\UpdatePaymentSettingsRequest;
use App\Http\Requests\Admin\UpdateShippingSettingsRequest;
use App\Settings\GeneralSettings;
use App\Settings\PaymentSettings;
use App\Settings\ShippingSettings;
use App\Support\Money;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class SettingsController extends Controller
{
    public function edit(GeneralSettings $general, PaymentSettings $payment, ShippingSettings $shipping): Response
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

        $settings->fill($data)->save();

        return back()->with('success', __('app.admin.saved'));
    }
}
