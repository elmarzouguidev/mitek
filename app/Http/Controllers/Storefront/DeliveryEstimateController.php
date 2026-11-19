<?php

namespace App\Http\Controllers\Storefront;

use App\Exceptions\ShippingUnavailable;
use App\Http\Controllers\Controller;
use App\Http\Requests\DeliveryEstimateRequest;
use App\Models\Core\Product\Product;
use App\Services\Core\Cart\CartService;
use App\Services\Core\Shipping\DeliveryEstimator;
use Illuminate\Http\JsonResponse;

class DeliveryEstimateController extends Controller
{
    /**
     * Delivery window and shipping fee for a city, for a product (with a quantity), the cart or a bare
     * estimate. The shipping fee uses the same grid as the checkout.
     */
    public function __invoke(DeliveryEstimateRequest $request, DeliveryEstimator $estimator, CartService $cart): JsonResponse
    {
        $weight = 0;

        if ($request->boolean('panier')) {
            $weight = $cart->summary()->weightGrams();
        } elseif ($request->filled('produit')) {
            $product = Product::query()->visible()->where('slug', $request->string('produit'))->firstOrFail();
            $weight = $product->weight_grams * max(1, $request->integer('quantite', 1));
        }

        try {
            $estimate = $estimator->estimate($request->string('ville')->toString(), $weight);
        } catch (ShippingUnavailable) {
            return response()->json(['message' => __('delivery.unavailable')], 422);
        }

        return response()->json($estimate->toArray())->header('Cache-Control', 'no-store, private');
    }
}
