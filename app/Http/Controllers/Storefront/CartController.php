<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Http\Requests\AddToCartRequest;
use App\Http\Requests\UpdateCartRequest;
use App\Models\Core\Product\Product;
use App\Services\Core\Cart\CartPayload;
use App\Services\Core\Cart\CartService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CartController extends Controller
{
    public function show(CartService $cart): View
    {
        return view('cart.show', ['summary' => $cart->summary()]);
    }

    /**
     * The priced cart as JSON, for the mini-cart drawer.
     */
    public function summary(CartPayload $payload): JsonResponse
    {
        return $this->json($payload->make());
    }

    public function store(AddToCartRequest $request, CartService $cart, CartPayload $payload): RedirectResponse|JsonResponse
    {
        $product = $this->visibleProduct($request->validated('product'));

        $cart->add($product, (int) $request->validated('quantite'));

        if ($request->expectsJson()) {
            return $this->json($payload->make($product));
        }

        return redirect()->route('cart.show')->with('success', __('orders.cart.added', ['name' => $product->name]));
    }

    public function update(UpdateCartRequest $request, CartService $cart, CartPayload $payload, string $slug): RedirectResponse|JsonResponse
    {
        $cart->set($this->visibleProduct($slug), (int) $request->validated('quantite'));

        if ($request->expectsJson()) {
            return $this->json($payload->make());
        }

        return redirect()->route('cart.show');
    }

    public function destroy(Request $request, CartService $cart, CartPayload $payload, string $slug): RedirectResponse|JsonResponse
    {
        $cart->remove($this->visibleProduct($slug)->id);

        if ($request->expectsJson()) {
            return $this->json($payload->make());
        }

        return redirect()->route('cart.show')->with('success', __('orders.cart.removed'));
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function json(array $data): JsonResponse
    {
        return response()->json($data)->header('Cache-Control', 'no-store, private');
    }

    private function visibleProduct(string $slug): Product
    {
        return Product::query()->visible()->where('slug', $slug)->firstOrFail();
    }
}
