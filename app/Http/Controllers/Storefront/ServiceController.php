<?php

namespace App\Http\Controllers\Storefront;

use App\Enums\Intervention;
use App\Enums\Urgency;
use App\Http\Controllers\Controller;
use App\Http\Requests\ServiceRequestForm;
use App\Models\Core\Content\FaqCategory;
use App\Models\Core\Service\Service;
use App\Services\Core\Service\SubmitServiceRequest;
use App\Support\MoroccanCities;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ServiceController extends Controller
{
    public function index(): View
    {
        return view('services.index', ['services' => Service::query()->active()->ordered()->get()]);
    }

    public function show(string $slug): View
    {
        $service = Service::query()->active()->where('slug', $slug)->firstOrFail();

        return view('services.show', [
            'service' => $service,
            'others' => Service::query()->active()->ordered()->whereKeyNot($service->id)->get(['id', 'title', 'slug']),
            'faqCategory' => FaqCategory::query()->where('slug', 'services')->with(['items' => fn ($items) => $items->active()])->first(),
            'interventions' => Intervention::cases(),
            'urgencies' => Urgency::cases(),
            'cities' => MoroccanCities::all(),
        ]);
    }

    public function request(ServiceRequestForm $request, SubmitServiceRequest $submit): RedirectResponse
    {
        $service = Service::query()->active()->where('slug', $request->validated('service'))->firstOrFail();

        $serviceRequest = $submit->handle($service, $request->safe()->except('service'));

        return redirect()
            ->route('services.show', $service->slug)
            ->with('success', __('services.sent', ['reference' => $serviceRequest->reference]));
    }
}
