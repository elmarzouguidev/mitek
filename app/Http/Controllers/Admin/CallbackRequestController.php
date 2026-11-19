<?php

namespace App\Http\Controllers\Admin;

use App\Enums\CallbackStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateCallbackRequestRequest;
use App\Models\Core\Content\CallbackRequest;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class CallbackRequestController extends Controller
{
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', CallbackRequest::class);

        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::enum(CallbackStatus::class)],
            'sort' => ['nullable', Rule::in(['created_at'])],
            'direction' => ['nullable', 'in:asc,desc'],
        ]);

        $callbacks = CallbackRequest::query()
            ->when($filters['q'] ?? null, function (Builder $query, string $term): void {
                $like = '%'.str_replace(['|', '%', '_'], ['||', '|%', '|_'], $term).'%';

                $query->where(fn (Builder $match) => $match
                    ->whereRaw("name LIKE ? ESCAPE '|'", [$like])
                    ->orWhereRaw("phone LIKE ? ESCAPE '|'", [$like]));
            })
            ->when($filters['status'] ?? null, fn (Builder $query, string $status) => $query->where('status', $status))
            ->orderBy($filters['sort'] ?? 'created_at', $filters['direction'] ?? 'desc')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Callbacks/Index', [
            'callbacks' => [
                'data' => $callbacks->getCollection()->map(fn (CallbackRequest $callback): array => [
                    'id' => $callback->id,
                    'name' => $callback->name,
                    'phone' => $callback->phone,
                    'subject' => $callback->subject->label(),
                    'time_slot' => $callback->time_slot->label(),
                    'status' => ['value' => $callback->status->value, 'label' => $callback->status->label(), 'tone' => $callback->status->tone()],
                    'created_at' => $callback->created_at->translatedFormat('d/m/Y H:i'),
                ])->values(),
                'meta' => [
                    'current_page' => $callbacks->currentPage(),
                    'last_page' => $callbacks->lastPage(),
                    'from' => $callbacks->firstItem(),
                    'to' => $callbacks->lastItem(),
                    'total' => $callbacks->total(),
                    'links' => $callbacks->linkCollection()->all(),
                ],
            ],
            'filters' => (object) $filters,
            'options' => ['statuses' => $this->statuses()],
        ]);
    }

    public function show(CallbackRequest $callback): Response
    {
        Gate::authorize('view', $callback);

        return Inertia::render('Callbacks/Show', [
            'callback' => [
                'id' => $callback->id,
                'name' => $callback->name,
                'phone' => $callback->phone,
                'subject' => $callback->subject->label(),
                'time_slot' => $callback->time_slot->label(),
                'note' => $callback->note,
                'status' => $callback->status->value,
                'internal_notes' => $callback->internal_notes,
                'created_at' => $callback->created_at->translatedFormat('d/m/Y H:i'),
                'contacted_at' => $callback->contacted_at?->translatedFormat('d/m/Y H:i'),
            ],
            'statuses' => $this->statuses(),
        ]);
    }

    public function update(UpdateCallbackRequestRequest $request, CallbackRequest $callback): RedirectResponse
    {
        Gate::authorize('update', $callback);

        $data = $request->validated();

        if ($data['status'] === CallbackStatus::Called->value && $callback->contacted_at === null) {
            $data['contacted_at'] = now();
        }

        $callback->update($data);

        return back()->with('success', __('admin.callbacks.updated'));
    }

    /**
     * @return array<int, array{id: string, name: string}>
     */
    private function statuses(): array
    {
        return array_map(fn (CallbackStatus $status): array => ['id' => $status->value, 'name' => $status->label()], CallbackStatus::cases());
    }
}
