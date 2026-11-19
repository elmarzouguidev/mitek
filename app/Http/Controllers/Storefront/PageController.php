<?php

namespace App\Http\Controllers\Storefront;

use App\Enums\PageKey;
use App\Http\Controllers\Controller;
use App\Models\Core\Content\FaqCategory;
use App\Models\Core\Content\Page;
use Illuminate\View\View;

class PageController extends Controller
{
    public function show(string $key): View
    {
        $pageKey = PageKey::from($key);
        $page = Page::query()->published()->where('key', $pageKey)->firstOrFail();

        $faqCategory = $pageKey->faqCategory() === null
            ? null
            : FaqCategory::query()->where('slug', $pageKey->faqCategory())->with(['items' => fn ($items) => $items->active()])->first();

        return view('pages.show', ['page' => $page, 'faqCategory' => $faqCategory]);
    }
}
