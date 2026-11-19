<?php

namespace App\Http\Controllers\Storefront;

use App\Enums\CarrierMode;
use App\Enums\PaymentMethod;
use App\Exceptions\ShippingUnavailable;
use App\Http\Controllers\Controller;
use App\Http\Requests\CheckoutRequest;
use App\Services\Core\Cart\CartService;
use App\Services\Core\Order\CheckoutPricing;
use App\Services\Core\Order\PaymentRules;
use App\Services\Core\Order\PlaceOrder;
use App\Support\MoroccanCities;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CheckoutController extends Controller
{
    public function show(Request $request, CartService $cart, PaymentRules $rules): View|RedirectResponse
    {
        $summary = $cart->summary();

        if ($summary->isEmpty()) {
            return redirect()->route('cart.show');
        }

        return view('checkout.show', [
            'summary' => $summary,
            'prefill' => $this->prefill($request),
            'cities' => MoroccanCities::all(),
            'methods' => PaymentMethod::cases(),
            'codLimit' => $rules->codLimit(),
        ]);
    }

    /**
     * Shipping cost, total and usable payment methods for a city and carrier choice (feeds the Alpine summary).
     */
    public function estimate(Request $request, CartService $cart, CheckoutPricing $pricing, PaymentRules $rules): JsonResponse
    {
        $validated = $request->validate([
            'ville' => ['required', 'string', 'max:100'],
            'transporteur' => ['required', Rule::enum(CarrierMode::class)],
        ]);

        $summary = $cart->summary();

        if ($summary->isEmpty()) {
            return response()->json(['message' => __('orders.errors.empty_cart')], 422);
        }

        try {
            $quote = $pricing->quote($summary, $validated['ville'], CarrierMode::from($validated['transporteur']));
        } catch (ShippingUnavailable $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'quote' => $quote,
            'methods' => array_map(fn (PaymentMethod $method): string => $method->value, $rules->availableFor($quote->totalTtc)),
        ]);
    }

    public function store(CheckoutRequest $request, PlaceOrder $placeOrder): RedirectResponse
    {
        $order = $placeOrder->handle($request->validated(), $request->user());

        return redirect()
            ->to(URL::signedRoute('orders.show', $order))
            ->with('success', __('orders.placed', ['reference' => $order->reference]));
    }

    /**
     * Pre-fills the form from the account: identity, company details (profile) and the default address.
     *
     * @return array<string, mixed>
     */
    private function prefill(Request $request): array
    {
        $user = $request->user();

        if ($user === null) {
            return [];
        }

        $profile = $user->profile()->first();
        $address = $user->addresses()->where('is_default', true)->first() ?? $user->addresses()->first();

        return [
            'contact_name' => $address?->contact_name ?? $user->name,
            'contact_email' => $user->email,
            'contact_phone' => $address?->phone ?? $profile?->phone,
            'is_company' => $profile?->isCompany() ?? false,
            'company_name' => $profile?->company_name,
            'ice' => $profile?->ice,
            'if_number' => $profile?->if_number,
            'rc_number' => $profile?->rc_number,
            'shipping_city' => $address?->city,
            'shipping_address' => $address?->address,
            'shipping_postal_code' => $address?->postal_code,
        ];
    }
}
