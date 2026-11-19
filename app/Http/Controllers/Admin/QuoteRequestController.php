<?php

namespace App\Http\Controllers\Admin;

use App\Enums\QuoteStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ReplyToQuoteRequest;
use App\Models\Core\Quote\QuoteRequest;
use App\Models\Core\Quote\QuoteRequestItem;
use App\Services\Core\Quote\ReplyToQuote;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class QuoteRequestController extends Controller
{
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', QuoteRequest::class);

        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::enum(QuoteStatus::class)],
            'sort' => ['nullable', Rule::in(['created_at', 'reference'])],
            'direction' => ['nullable', 'in:asc,desc'],
        ]);

        $quotes = QuoteRequest::query()
            ->withCount('items')
            ->when($filters['q'] ?? null, function (Builder $query, string $term): void {
                $like = '%'.str_replace(['|', '%', '_'], ['||', '|%', '|_'], $term).'%';

                $query->where(fn (Builder $match) => $match
                    ->whereRaw("reference LIKE ? ESCAPE '|'", [$like])
                    ->orWhereRaw("contact_name LIKE ? ESCAPE '|'", [$like])
                    ->orWhereRaw("contact_email LIKE ? ESCAPE '|'", [$like])
                    ->orWhereRaw("company_name LIKE ? ESCAPE '|'", [$like]));
            })
            ->when($filters['status'] ?? null, fn (Builder $query, string $status) => $query->where('status', $status))
            ->orderBy($filters['sort'] ?? 'created_at', $filters['direction'] ?? 'desc')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (QuoteRequest $quote): array => [
                'id' => $quote->id,
                'reference' => $quote->reference,
                'customer' => $quote->company_name ?: $quote->contact_name,
                'email' => $quote->contact_email,
                'items_count' => $quote->items_count,
                'status' => ['value' => $quote->status->value, 'label' => $quote->status->label(), 'tone' => $quote->status->tone()],
                'created_at' => $quote->created_at->translatedFormat('d/m/Y H:i'),
            ]);

        return Inertia::render('Quotes/Index', [
            'quotes' => ['data' => $quotes->items(), 'meta' => [
                'current_page' => $quotes->currentPage(),
                'last_page' => $quotes->lastPage(),
                'from' => $quotes->firstItem(),
                'to' => $quotes->lastItem(),
                'total' => $quotes->total(),
                'links' => $quotes->linkCollection()->all(),
            ]],
            'filters' => (object) $filters,
            'options' => [
                'statuses' => array_map(fn (QuoteStatus $status): array => ['id' => $status->value, 'name' => $status->label()], QuoteStatus::cases()),
            ],
        ]);
    }

    public function show(QuoteRequest $quote): Response
    {
        Gate::authorize('view', $quote);

        $quote->load(['items', 'repliedBy']);

        return Inertia::render('Quotes/Show', [
            'quote' => [
                'id' => $quote->id,
                'reference' => $quote->reference,
                'status' => ['value' => $quote->status->value, 'label' => $quote->status->label(), 'tone' => $quote->status->tone()],
                'created_at' => $quote->created_at->translatedFormat('d/m/Y H:i'),
                'contact' => ['name' => $quote->contact_name, 'email' => $quote->contact_email, 'phone' => $quote->contact_phone],
                'company' => $quote->company_name ? ['name' => $quote->company_name, 'ice' => $quote->ice] : null,
                'message' => $quote->message,
                'admin_reply' => $quote->admin_reply,
                'replied_at' => $quote->replied_at?->translatedFormat('d/m/Y H:i'),
                'replied_by' => $quote->repliedBy?->name,
                'items' => $quote->items->map(fn (QuoteRequestItem $item): array => [
                    'id' => $item->id,
                    'reference' => $item->reference,
                    'name' => $item->name,
                    'quantity' => $item->quantity,
                    'unit_price_ht' => $item->unit_price_ht->format(),
                ])->values(),
            ],
        ]);
    }

    public function reply(ReplyToQuoteRequest $request, QuoteRequest $quote, ReplyToQuote $reply): RedirectResponse
    {
        $reply->handle($quote, $request->user(), $request->validated('admin_reply'));

        return back()->with('success', __('admin.quotes.replied'));
    }

    public function close(QuoteRequest $quote): RedirectResponse
    {
        Gate::authorize('update', $quote);

        $quote->update(['status' => QuoteStatus::Closed]);

        return back()->with('success', __('admin.quotes.closed'));
    }
}
