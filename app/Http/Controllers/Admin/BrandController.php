<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\BrandRequest;
use App\Http\Resources\Admin\BrandResource;
use App\Models\Core\Product\Brand;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class BrandController extends Controller
{
    private const SORTABLE = ['name', 'created_at'];

    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Brand::class);

        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'sort' => ['nullable', 'in:'.implode(',', self::SORTABLE)],
            'direction' => ['nullable', 'in:asc,desc'],
        ]);

        $brands = Brand::query()
            ->with('media')
            ->withCount('products')
            ->when($filters['q'] ?? null, function (Builder $query, string $term): void {
                $like = '%'.str_replace(['|', '%', '_'], ['||', '|%', '|_'], $term).'%';
                $query->whereRaw("name LIKE ? ESCAPE '|'", [$like]);
            })
            ->orderBy($filters['sort'] ?? 'name', $filters['direction'] ?? 'asc')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Brands/Index', [
            'brands' => BrandResource::collection($brands),
            'filters' => (object) $filters,
        ]);
    }

    public function create(): Response
    {
        Gate::authorize('create', Brand::class);

        return Inertia::render('Brands/Form', ['brand' => null]);
    }

    public function store(BrandRequest $request): RedirectResponse
    {
        $brand = Brand::create($request->safe()->only(['name', 'is_active']));
        $this->syncLogo($request, $brand);

        return redirect()->route('admin.brands.index')->with('success', __('admin.brands.created'));
    }

    public function edit(Brand $brand): Response
    {
        Gate::authorize('update', $brand);

        $brand->load('media');

        return Inertia::render('Brands/Form', [
            'brand' => [
                ...$brand->only(['id', 'name', 'is_active']),
                'logo_url' => $brand->getFirstMediaUrl('logo', 'thumb') ?: null,
            ],
        ]);
    }

    public function update(BrandRequest $request, Brand $brand): RedirectResponse
    {
        $brand->update($request->safe()->only(['name', 'is_active']));
        $this->syncLogo($request, $brand);

        return redirect()->route('admin.brands.index')->with('success', __('admin.brands.updated'));
    }

    public function destroy(Brand $brand): RedirectResponse
    {
        Gate::authorize('delete', $brand);

        $brand->delete();

        return redirect()->route('admin.brands.index')->with('success', __('admin.brands.deleted'));
    }

    private function syncLogo(BrandRequest $request, Brand $brand): void
    {
        if ($request->hasFile('logo')) {
            $brand->addMediaFromRequest('logo')->toMediaCollection('logo');
        } elseif ($request->boolean('remove_logo')) {
            $brand->clearMediaCollection('logo');
        }
    }
}
