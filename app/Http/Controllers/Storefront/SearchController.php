<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Http\Requests\SearchRequest;
use App\Models\Core\Product\Product;
use App\Services\Core\Product\PriceCalculator;
use App\Services\Core\Product\ProductCatalog;
use App\Services\Core\Product\ProductSearch;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class SearchController extends Controller
{
    public const SUGGESTION_LIMIT = 8;

    public const MIN_TERM_LENGTH = 2;

    public function index(SearchRequest $request, ProductSearch $search): View
    {
        $term = $request->term();

        $products = mb_strlen($term) >= self::MIN_TERM_LENGTH
            ? $search->search($term)->paginate(ProductCatalog::PER_PAGE)->withQueryString()
            : null;

        return view('catalog.search', ['term' => $term, 'products' => $products]);
    }

    public function suggestions(SearchRequest $request, ProductSearch $search, PriceCalculator $calculator): JsonResponse
    {
        $term = $request->term();

        if (mb_strlen($term) < self::MIN_TERM_LENGTH) {
            return response()->json(['data' => []]);
        }

        $products = $search->search($term)->limit(self::SUGGESTION_LIMIT)->get();

        return response()->json([
            'data' => $products->map(fn (Product $product): array => [
                'name' => $product->name,
                'reference' => $product->reference,
                'brand' => $product->brand?->name,
                'price' => $calculator->unitPriceTtc($product)->format(),
                'image' => $product->getFirstMediaUrl(Product::GALLERY, 'thumb') ?: null,
                'url' => route('products.show', $product->slug),
            ])->values(),
        ]);
    }
}
