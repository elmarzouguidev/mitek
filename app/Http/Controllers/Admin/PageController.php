<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PageRequest;
use App\Models\Core\Content\Page;
use App\Services\Content\ContentRenderer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The CMS pages are a fixed set (see PageKey): administrators edit them, they do not create or delete them.
 */
class PageController extends Controller
{
    public function index(): Response
    {
        Gate::authorize('viewAny', Page::class);

        return Inertia::render('Pages/Index', [
            'pages' => Page::query()->orderBy('id')->get()->map(fn (Page $page): array => [
                'id' => $page->id,
                'title' => $page->title,
                'url' => route('pages.'.$page->key->value),
                'is_published' => $page->is_published,
                'updated_at' => $page->updated_at->translatedFormat('d/m/Y H:i'),
            ])->values(),
        ]);
    }

    public function edit(Page $page, ContentRenderer $renderer): Response
    {
        Gate::authorize('update', $page);

        return Inertia::render('Pages/Form', [
            'page' => [
                ...$page->only(['id', 'title', 'content', 'meta_title', 'meta_description', 'is_published']),
                'url' => route('pages.'.$page->key->value),
            ],
            'tokens' => collect($renderer->values())->map(fn ($value, string $token): array => [
                'token' => '{{'.$token.'}}',
                'value' => filled($value) ? (string) $value : ContentRenderer::PLACEHOLDER,
            ])->values(),
        ]);
    }

    public function update(PageRequest $request, Page $page): RedirectResponse
    {
        $page->update($request->validated());

        return back()->with('success', __('admin.pages.saved'));
    }
}
