<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\FaqCategoryRequest;
use App\Http\Requests\Admin\FaqItemRequest;
use App\Models\Core\Content\FaqCategory;
use App\Models\Core\Content\FaqItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class FaqController extends Controller
{
    public function index(): Response
    {
        Gate::authorize('viewAny', FaqCategory::class);

        return Inertia::render('Faq/Index', [
            'categories' => FaqCategory::query()->with('items')->orderBy('sort_order')->orderBy('id')->get()->map(fn (FaqCategory $category): array => [
                'id' => $category->id,
                'name' => $category->name,
                'slug' => $category->slug,
                'sort_order' => $category->sort_order,
                'items' => $category->items->map(fn (FaqItem $item): array => [
                    'id' => $item->id,
                    'question' => $item->question,
                    'is_active' => $item->is_active,
                    'sort_order' => $item->sort_order,
                ])->values(),
            ])->values(),
        ]);
    }

    public function createCategory(): Response
    {
        Gate::authorize('create', FaqCategory::class);

        return Inertia::render('Faq/CategoryForm', ['category' => null]);
    }

    public function storeCategory(FaqCategoryRequest $request): RedirectResponse
    {
        Gate::authorize('create', FaqCategory::class);

        FaqCategory::create($request->validated());

        return redirect()->route('admin.faq.index')->with('success', __('admin.faq.saved'));
    }

    public function editCategory(FaqCategory $category): Response
    {
        Gate::authorize('update', $category);

        return Inertia::render('Faq/CategoryForm', ['category' => $category->only(['id', 'name', 'slug', 'sort_order'])]);
    }

    public function updateCategory(FaqCategoryRequest $request, FaqCategory $category): RedirectResponse
    {
        Gate::authorize('update', $category);

        $category->update($request->validated());

        return redirect()->route('admin.faq.index')->with('success', __('admin.faq.saved'));
    }

    public function destroyCategory(FaqCategory $category): RedirectResponse
    {
        Gate::authorize('delete', $category);

        $category->delete();

        return redirect()->route('admin.faq.index')->with('success', __('admin.faq.deleted'));
    }

    public function createItem(): Response
    {
        Gate::authorize('create', FaqItem::class);

        return Inertia::render('Faq/ItemForm', ['item' => null, 'categories' => $this->categoryOptions()]);
    }

    public function storeItem(FaqItemRequest $request): RedirectResponse
    {
        Gate::authorize('create', FaqItem::class);

        FaqItem::create($request->validated());

        return redirect()->route('admin.faq.index')->with('success', __('admin.faq.saved'));
    }

    public function editItem(FaqItem $item): Response
    {
        Gate::authorize('update', $item);

        return Inertia::render('Faq/ItemForm', [
            'item' => $item->only(['id', 'faq_category_id', 'question', 'answer', 'sort_order', 'is_active']),
            'categories' => $this->categoryOptions(),
        ]);
    }

    public function updateItem(FaqItemRequest $request, FaqItem $item): RedirectResponse
    {
        Gate::authorize('update', $item);

        $item->update($request->validated());

        return redirect()->route('admin.faq.index')->with('success', __('admin.faq.saved'));
    }

    public function destroyItem(FaqItem $item): RedirectResponse
    {
        Gate::authorize('delete', $item);

        $item->delete();

        return redirect()->route('admin.faq.index')->with('success', __('admin.faq.deleted'));
    }

    /**
     * @return array<int, array{id: int, name: string}>
     */
    private function categoryOptions(): array
    {
        return FaqCategory::query()->orderBy('sort_order')->orderBy('id')->get(['id', 'name'])->toArray();
    }
}
