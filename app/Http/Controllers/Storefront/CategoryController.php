<?php

namespace App\Http\Controllers\Storefront;

use App\Enums\ProductSort;
use App\Http\Controllers\Controller;
use App\Http\Requests\CatalogFilterRequest;
use App\Models\Core\Product\Category;
use App\Services\Core\Product\ProductCatalog;
use App\Services\Seo\CatalogSeo;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function show(CatalogFilterRequest $request, ProductCatalog $catalog, CatalogSeo $seo, string $slug): View
    {
        $category = Category::query()
            ->visible()
            ->with(['parent', 'children' => fn ($children) => $children->where('is_active', true), 'media'])
            ->where('slug', $slug)
            ->firstOrFail();

        $filters = $request->filters();

        return view('catalog.category', [
            'category' => $category,
            'filters' => $filters,
            'products' => $catalog->listing($category, $filters)->paginate(ProductCatalog::PER_PAGE)->withQueryString(),
            'brands' => $catalog->brandsFor($category),
            'subcategories' => $category->children,
            'sorts' => ProductSort::cases(),
            'seo' => $seo->forCategory($category, $filters, max(1, (int) $request->query('page', 1))),
        ]);
    }
}
