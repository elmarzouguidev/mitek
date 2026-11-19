<?php

namespace App\Services\Core\Product;

use App\Models\Core\Product\Category;
use Illuminate\Support\Facades\Cache;

/**
 * Category tree shown in the storefront header. Cached; flushed whenever a category is saved or deleted.
 */
class CatalogMenu
{
    /**
     * @return array<int, array{name: string, slug: string, children: array<int, array{name: string, slug: string}>}>
     */
    public function tree(): array
    {
        return Cache::remember(Category::MENU_CACHE_KEY, now()->addHours(6), fn (): array => Category::query()
            ->roots()
            ->visible()
            ->ordered()
            ->with(['children' => fn ($children) => $children->where('is_active', true)])
            ->get()
            ->map(fn (Category $category): array => [
                'name' => $category->name,
                'slug' => $category->slug,
                'children' => $category->children->map(fn (Category $child): array => [
                    'name' => $child->name,
                    'slug' => $child->slug,
                ])->all(),
            ])
            ->all());
    }
}
