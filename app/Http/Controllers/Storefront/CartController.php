<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Http\Requests\AddToCartRequest;
use App\Http\Requests\UpdateCartRequest;
use App\Models\Core\Product\Product;
use App\Services\Core\Cart\CartService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CartController extends Controller
{
    public function show(CartService $cart): View
    {
        return view('cart.show', ['summary' => $cart->summary()]);
    }

    public function store(AddToCartRequest $request, CartService $cart): RedirectResponse
    {
        $product = $this->visibleProduct($request->validated('product'));

        $cart->add($product, (int) $request->validated('quantite'));

        return redirect()->route('cart.show')->with('success', __('orders.cart.added', ['name' => $product->name]));
    }

    public function update(UpdateCartRequest $request, CartService $cart, string $slug): RedirectResponse
    {
        $cart->set($this->visibleProduct($slug), (int) $request->validated('quantite'));

        return redirect()->route('cart.show');
    }

    public function destroy(CartService $cart, string $slug): RedirectResponse
    {
        $cart->remove($this->visibleProduct($slug)->id);

        return redirect()->route('cart.show')->with('success', __('orders.cart.removed'));
    }

    private function visibleProduct(string $slug): Product
    {
        return Product::query()->visible()->where('slug', $slug)->firstOrFail();
    }
}
