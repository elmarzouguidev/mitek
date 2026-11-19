<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CategoryRequest;
use App\Models\Core\Product\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class CategoryController extends Controller
{
    public function index(): Response
    {
        Gate::authorize('viewAny', Category::class);

        $roots = Category::query()->roots()->ordered()->withCount('products')->with(['children' => fn ($children) => $children->withCount('products')])->get();

        return Inertia::render('Categories/Index', [
            'categories' => $roots->flatMap(fn (Category $root) => collect([$root])->merge($root->children))
                ->map(fn (Category $category): array => [
                    'id' => $category->id,
                    'name' => $category->name,
                    'slug' => $category->slug,
                    'is_child' => ! $category->isRoot(),
                    'is_active' => $category->is_active,
                    'sort_order' => $category->sort_order,
                    'products_count' => $category->products_count,
                ])->values(),
        ]);
    }

    public function create(): Response
    {
        Gate::authorize('create', Category::class);

        return Inertia::render('Categories/Form', ['category' => null, 'parents' => $this->parents()]);
    }

    public function store(CategoryRequest $request): RedirectResponse
    {
        $category = Category::create($request->safe()->except(['image', 'remove_image']));
        $this->syncImage($request, $category);

        return redirect()->route('admin.categories.index')->with('success', __('admin.categories.created'));
    }

    public function edit(Category $category): Response
    {
        Gate::authorize('update', $category);

        $category->load('media');

        return Inertia::render('Categories/Form', [
            'category' => [
                ...$category->only(['id', 'name', 'parent_id', 'description', 'sort_order', 'is_active', 'meta_title', 'meta_description']),
                'image_url' => $category->getFirstMediaUrl('image', 'card') ?: null,
            ],
            'parents' => $this->parents($category),
        ]);
    }

    public function update(CategoryRequest $request, Category $category): RedirectResponse
    {
        $category->update($request->safe()->except(['image', 'remove_image']));
        $this->syncImage($request, $category);

        return redirect()->route('admin.categories.index')->with('success', __('admin.categories.updated'));
    }

    public function destroy(Category $category): RedirectResponse
    {
        Gate::authorize('delete', $category);

        if ($category->children()->exists() || $category->products()->exists()) {
            return back()->with('error', __('admin.categories.not_empty'));
        }

        $category->delete();

        return redirect()->route('admin.categories.index')->with('success', __('admin.categories.deleted'));
    }

    /**
     * Root categories that can receive subcategories (two levels at most).
     *
     * @return array<int, array{id: int, name: string}>
     */
    private function parents(?Category $except = null): array
    {
        return Category::query()
            ->roots()
            ->ordered()
            ->when($except, fn ($query) => $query->whereKeyNot($except->id))
            ->get(['id', 'name'])
            ->toArray();
    }

    private function syncImage(CategoryRequest $request, Category $category): void
    {
        if ($request->hasFile('image')) {
            $category->addMediaFromRequest('image')->toMediaCollection('image');
        } elseif ($request->boolean('remove_image')) {
            $category->clearMediaCollection('image');
        }
    }
}
