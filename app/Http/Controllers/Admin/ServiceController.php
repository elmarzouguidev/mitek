<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ServiceFormRequest;
use App\Models\Core\Service\Service;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class ServiceController extends Controller
{
    public function index(): Response
    {
        Gate::authorize('viewAny', Service::class);

        return Inertia::render('Services/Index', [
            'services' => Service::query()->withCount('requests')->ordered()->get()->map(fn (Service $service): array => [
                'id' => $service->id,
                'title' => $service->title,
                'slug' => $service->slug,
                'icon' => $service->icon,
                'sort_order' => $service->sort_order,
                'is_active' => $service->is_active,
                'requests_count' => $service->requests_count,
            ])->values(),
        ]);
    }

    public function create(): Response
    {
        Gate::authorize('create', Service::class);

        return Inertia::render('Services/Form', ['service' => null, 'icons' => Service::ICONS]);
    }

    public function store(ServiceFormRequest $request): RedirectResponse
    {
        Service::create($request->validated());

        return redirect()->route('admin.services.index')->with('success', __('admin.services.saved'));
    }

    public function edit(Service $service): Response
    {
        Gate::authorize('update', $service);

        return Inertia::render('Services/Form', [
            'service' => $service->only(['id', 'title', 'slug', 'icon', 'summary', 'bullets', 'content', 'meta_title', 'meta_description', 'sort_order', 'is_active']),
            'icons' => Service::ICONS,
        ]);
    }

    public function update(ServiceFormRequest $request, Service $service): RedirectResponse
    {
        $service->update($request->validated());

        return redirect()->route('admin.services.index')->with('success', __('admin.services.saved'));
    }

    public function destroy(Service $service): RedirectResponse
    {
        Gate::authorize('delete', $service);

        $service->delete();

        return redirect()->route('admin.services.index')->with('success', __('admin.services.deleted'));
    }
}
