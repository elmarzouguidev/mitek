<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Http\Requests\SearchRequest;
use App\Services\Core\Product\ProductCatalog;
use App\Services\Core\Product\ProductSearch;
use App\Services\Core\Product\SearchSuggester;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class SearchController extends Controller
{
    public const MIN_TERM_LENGTH = 2;

    public function index(SearchRequest $request, ProductSearch $search): View
    {
        $term = $request->term();

        $products = mb_strlen($term) >= self::MIN_TERM_LENGTH
            ? $search->search($term)->paginate(ProductCatalog::PER_PAGE)->withQueryString()
            : null;

        return view('catalog.search', ['term' => $term, 'products' => $products]);
    }

    /**
     * Grouped autocomplete suggestions for the header search (see SearchSuggester).
     */
    public function suggestions(SearchRequest $request, SearchSuggester $suggester): JsonResponse
    {
        return response()->json($suggester->suggest($request->term()))->header('Cache-Control', 'private, max-age=30');
    }
}
