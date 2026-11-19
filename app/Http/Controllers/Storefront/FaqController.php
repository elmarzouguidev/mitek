<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Core\Content\FaqCategory;
use Illuminate\View\View;

class FaqController extends Controller
{
    public function index(): View
    {
        $categories = FaqCategory::query()
            ->with(['items' => fn ($items) => $items->active()])
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->filter(fn (FaqCategory $category): bool => $category->items->isNotEmpty())
            ->values();

        return view('faq.index', ['categories' => $categories]);
    }
}
