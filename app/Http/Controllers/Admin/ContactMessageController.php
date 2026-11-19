<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Core\Content\ContactMessage;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class ContactMessageController extends Controller
{
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', ContactMessage::class);

        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', 'in:unread,read'],
        ]);

        $messages = ContactMessage::query()
            ->when($filters['q'] ?? null, function (Builder $query, string $term): void {
                $like = '%'.str_replace(['|', '%', '_'], ['||', '|%', '|_'], $term).'%';

                $query->where(fn (Builder $match) => $match
                    ->whereRaw("name LIKE ? ESCAPE '|'", [$like])
                    ->orWhereRaw("email LIKE ? ESCAPE '|'", [$like])
                    ->orWhereRaw("subject LIKE ? ESCAPE '|'", [$like]));
            })
            ->when(($filters['status'] ?? null) === 'unread', fn (Builder $query) => $query->unread())
            ->when(($filters['status'] ?? null) === 'read', fn (Builder $query) => $query->whereNotNull('read_at'))
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Messages/Index', [
            'messages' => [
                'data' => $messages->getCollection()->map(fn (ContactMessage $message): array => [
                    'id' => $message->id,
                    'name' => $message->name,
                    'email' => $message->email,
                    'subject' => $message->subject,
                    'is_read' => $message->isRead(),
                    'created_at' => $message->created_at->translatedFormat('d/m/Y H:i'),
                ])->values(),
                'meta' => [
                    'current_page' => $messages->currentPage(),
                    'last_page' => $messages->lastPage(),
                    'from' => $messages->firstItem(),
                    'to' => $messages->lastItem(),
                    'total' => $messages->total(),
                    'links' => $messages->linkCollection()->all(),
                ],
            ],
            'filters' => (object) $filters,
            'options' => ['statuses' => [['id' => 'unread', 'name' => 'Non lus'], ['id' => 'read', 'name' => 'Lus']]],
        ]);
    }

    /**
     * Opening a message marks it as read.
     */
    public function show(ContactMessage $message): Response
    {
        Gate::authorize('view', $message);

        if (! $message->isRead()) {
            $message->update(['read_at' => now()]);
        }

        return Inertia::render('Messages/Show', [
            'message' => [
                'id' => $message->id,
                'name' => $message->name,
                'email' => $message->email,
                'phone' => $message->phone,
                'subject' => $message->subject,
                'body' => $message->message,
                'created_at' => $message->created_at->translatedFormat('d/m/Y H:i'),
            ],
        ]);
    }

    public function markUnread(ContactMessage $message): RedirectResponse
    {
        Gate::authorize('update', $message);

        $message->update(['read_at' => null]);

        return redirect()->route('admin.messages.index')->with('success', __('admin.messages.unread'));
    }

    public function destroy(ContactMessage $message): RedirectResponse
    {
        Gate::authorize('delete', $message);

        $message->delete();

        return redirect()->route('admin.messages.index')->with('success', __('admin.messages.deleted'));
    }
}
