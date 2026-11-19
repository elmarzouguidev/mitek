<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\BulkProductRequest;
use App\Http\Requests\Admin\ProductRequest;
use App\Http\Resources\Admin\ProductFormResource;
use App\Http\Resources\Admin\ProductListResource;
use App\Models\Core\Product\Brand;
use App\Models\Core\Product\Category;
use App\Models\Core\Product\Product;
use App\Services\Core\Product\ProductWriter;
use App\Settings\GeneralSettings;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class ProductController extends Controller
{
    private const SORTABLE = ['name', 'reference', 'price_ht', 'stock_quantity', 'created_at'];

    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Product::class);

        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'category_id' => ['nullable', 'integer'],
            'brand_id' => ['nullable', 'integer'],
            'status' => ['nullable', 'in:active,inactive'],
            'stock' => ['nullable', 'in:low,out'],
            'sort' => ['nullable', 'in:'.implode(',', self::SORTABLE)],
            'direction' => ['nullable', 'in:asc,desc'],
        ]);

        $products = Product::query()
            ->with(['category', 'brand', 'media'])
            ->when($filters['q'] ?? null, function (Builder $query, string $term): void {
                $like = '%'.str_replace(['|', '%', '_'], ['||', '|%', '|_'], $term).'%';

                $query->where(fn (Builder $match) => $match
                    ->whereRaw("products.name LIKE ? ESCAPE '|'", [$like])
                    ->orWhereRaw("products.reference LIKE ? ESCAPE '|'", [$like]));
            })
            ->when($filters['category_id'] ?? null, fn (Builder $query, int|string $id) => $query->where('category_id', $id))
            ->when($filters['brand_id'] ?? null, fn (Builder $query, int|string $id) => $query->where('brand_id', $id))
            ->when(($filters['status'] ?? null) === 'active', fn (Builder $query) => $query->where('is_active', true))
            ->when(($filters['status'] ?? null) === 'inactive', fn (Builder $query) => $query->where('is_active', false))
            ->when(($filters['stock'] ?? null) === 'low', fn (Builder $query) => $query->lowStock())
            ->when(($filters['stock'] ?? null) === 'out', fn (Builder $query) => $query->where('stock_quantity', 0))
            ->orderBy($filters['sort'] ?? 'created_at', $filters['direction'] ?? 'desc')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Products/Index', [
            'products' => ProductListResource::collection($products),
            'filters' => (object) $filters,
            'options' => $this->options(),
        ]);
    }

    public function create(GeneralSettings $settings): Response
    {
        Gate::authorize('create', Product::class);

        return Inertia::render('Products/Form', [
            'product' => null,
            'options' => $this->options(),
            'defaultVatRate' => $settings->vat_rate,
        ]);
    }

    public function store(ProductRequest $request, ProductWriter $writer): RedirectResponse
    {
        $product = $writer->save(new Product, $request->validated());

        return redirect()
            ->route('admin.products.edit', $product)
            ->with('success', __('admin.products.created'));
    }

    public function edit(Product $product, GeneralSettings $settings): Response
    {
        Gate::authorize('update', $product);

        $product->load(['priceTiers', 'specifications', 'compatibleModels', 'media']);

        return Inertia::render('Products/Form', [
            'product' => ProductFormResource::make($product)->resolve(),
            'options' => $this->options(),
            'defaultVatRate' => $settings->vat_rate,
        ]);
    }

    public function update(ProductRequest $request, Product $product, ProductWriter $writer): RedirectResponse
    {
        $writer->save($product, $request->validated());

        return back()->with('success', __('admin.products.updated'));
    }

    public function destroy(Product $product): RedirectResponse
    {
        Gate::authorize('delete', $product);

        $product->delete();

        return redirect()->route('admin.products.index')->with('success', __('admin.products.deleted'));
    }

    public function bulk(BulkProductRequest $request): RedirectResponse
    {
        $isActive = $request->validated('action') === 'activate';

        $count = Product::query()->whereKey($request->validated('ids'))->update(['is_active' => $isActive]);

        return back()->with('success', trans_choice('admin.products.bulk_done', $count, ['count' => $count]));
    }

    /**
     * @return array{categories: array<int, array{id: int, name: string}>, brands: array<int, array{id: int, name: string}>}
     */
    private function options(): array
    {
        $categories = Category::query()->roots()->ordered()->with('children')->get()
            ->flatMap(fn (Category $root) => collect([$root])->merge($root->children)
                ->map(fn (Category $category): array => [
                    'id' => $category->id,
                    'name' => $category->isRoot() ? $category->name : $root->name.' › '.$category->name,
                ]))
            ->values()
            ->all();

        return [
            'categories' => $categories,
            'brands' => Brand::query()->orderBy('name')->get(['id', 'name'])->toArray(),
        ];
    }
}
