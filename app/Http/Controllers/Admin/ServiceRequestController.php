<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ServiceRequestStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateServiceRequestRequest;
use App\Models\Core\Service\ServiceRequest;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ServiceRequestController extends Controller
{
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', ServiceRequest::class);

        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::enum(ServiceRequestStatus::class)],
            'sort' => ['nullable', Rule::in(['created_at', 'reference'])],
            'direction' => ['nullable', 'in:asc,desc'],
        ]);

        $requests = ServiceRequest::query()
            ->when($filters['q'] ?? null, function (Builder $query, string $term): void {
                $like = '%'.str_replace(['|', '%', '_'], ['||', '|%', '|_'], $term).'%';

                $query->where(fn (Builder $match) => $match
                    ->whereRaw("reference LIKE ? ESCAPE '|'", [$like])
                    ->orWhereRaw("contact_name LIKE ? ESCAPE '|'", [$like])
                    ->orWhereRaw("company_name LIKE ? ESCAPE '|'", [$like])
                    ->orWhereRaw("email LIKE ? ESCAPE '|'", [$like])
                    ->orWhereRaw("service_title LIKE ? ESCAPE '|'", [$like]));
            })
            ->when($filters['status'] ?? null, fn (Builder $query, string $status) => $query->where('status', $status))
            ->orderBy($filters['sort'] ?? 'created_at', $filters['direction'] ?? 'desc')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('ServiceRequests/Index', [
            'requests' => [
                'data' => $requests->getCollection()->map(fn (ServiceRequest $serviceRequest): array => [
                    'id' => $serviceRequest->id,
                    'reference' => $serviceRequest->reference,
                    'service' => $serviceRequest->service_title,
                    'customer' => $serviceRequest->company_name ?: $serviceRequest->contact_name,
                    'city' => $serviceRequest->city,
                    'urgency' => $serviceRequest->urgency->label(),
                    'status' => ['value' => $serviceRequest->status->value, 'label' => $serviceRequest->status->label(), 'tone' => $serviceRequest->status->tone()],
                    'created_at' => $serviceRequest->created_at->translatedFormat('d/m/Y H:i'),
                ])->values(),
                'meta' => [
                    'current_page' => $requests->currentPage(),
                    'last_page' => $requests->lastPage(),
                    'from' => $requests->firstItem(),
                    'to' => $requests->lastItem(),
                    'total' => $requests->total(),
                    'links' => $requests->linkCollection()->all(),
                ],
            ],
            'filters' => (object) $filters,
            'options' => ['statuses' => array_map(fn (ServiceRequestStatus $status): array => ['id' => $status->value, 'name' => $status->label()], ServiceRequestStatus::cases())],
        ]);
    }

    public function show(ServiceRequest $serviceRequest): Response
    {
        Gate::authorize('view', $serviceRequest);

        return Inertia::render('ServiceRequests/Show', [
            'serviceRequest' => [
                'id' => $serviceRequest->id,
                'reference' => $serviceRequest->reference,
                'service' => $serviceRequest->service_title,
                'status' => $serviceRequest->status->value,
                'status_label' => $serviceRequest->status->label(),
                'created_at' => $serviceRequest->created_at->translatedFormat('d/m/Y H:i'),
                'contact' => ['name' => $serviceRequest->contact_name, 'company' => $serviceRequest->company_name, 'phone' => $serviceRequest->phone, 'email' => $serviceRequest->email, 'city' => $serviceRequest->city],
                'intervention' => $serviceRequest->intervention->label(),
                'urgency' => $serviceRequest->urgency->label(),
                'message' => $serviceRequest->message,
                'internal_notes' => $serviceRequest->internal_notes,
            ],
            'statuses' => array_map(fn (ServiceRequestStatus $status): array => ['id' => $status->value, 'name' => $status->label()], ServiceRequestStatus::cases()),
        ]);
    }

    public function update(UpdateServiceRequestRequest $request, ServiceRequest $serviceRequest): RedirectResponse
    {
        $serviceRequest->update($request->validated());

        return back()->with('success', __('admin.service_requests.updated'));
    }
}
