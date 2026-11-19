<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Core\Product\Product;
use App\Services\Core\Product\PriceCalculator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function show(string $slug): View
    {
        $product = $this->visibleProduct($slug, [
            'media', 'brand', 'category.parent', 'priceTiers', 'specifications', 'compatibleModels',
        ]);

        $related = Product::query()
            ->visible()
            ->with(['media', 'brand', 'priceTiers'])
            ->where('category_id', $product->category_id)
            ->whereKeyNot($product->id)
            ->orderByDesc('is_featured')
            ->orderBy('name')
            ->limit(4)
            ->get();

        return view('catalog.product', ['product' => $product, 'related' => $related]);
    }

    /**
     * Price of a quantity, resolved by the same calculator the cart will use (feeds the Alpine price box).
     */
    public function price(Request $request, PriceCalculator $calculator, string $slug): JsonResponse
    {
        $validated = $request->validate(['quantite' => ['required', 'integer', 'min:1', 'max:100000']]);

        $product = $this->visibleProduct($slug, ['priceTiers']);

        return response()->json($calculator->quote($product, (int) $validated['quantite']));
    }

    /**
     * @param  array<int, string>  $with
     */
    private function visibleProduct(string $slug, array $with): Product
    {
        return Product::query()->visible()->with($with)->where('slug', $slug)->firstOrFail();
    }
}
