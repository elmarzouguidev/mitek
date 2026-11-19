<?php

namespace App\Http\Controllers\Admin;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\OrderDetailResource;
use App\Http\Resources\Admin\OrderListResource;
use App\Models\Core\Order\Order;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class OrderController extends Controller
{
    private const SORTABLE = ['created_at', 'total_ttc', 'reference'];

    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Order::class);

        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::enum(OrderStatus::class)],
            'payment_method' => ['nullable', Rule::enum(PaymentMethod::class)],
            'sort' => ['nullable', Rule::in(self::SORTABLE)],
            'direction' => ['nullable', 'in:asc,desc'],
        ]);

        $orders = Order::query()
            ->when($filters['q'] ?? null, function (Builder $query, string $term): void {
                $like = '%'.str_replace(['|', '%', '_'], ['||', '|%', '|_'], $term).'%';

                $query->where(fn (Builder $match) => $match
                    ->whereRaw("reference LIKE ? ESCAPE '|'", [$like])
                    ->orWhereRaw("contact_name LIKE ? ESCAPE '|'", [$like])
                    ->orWhereRaw("contact_email LIKE ? ESCAPE '|'", [$like])
                    ->orWhereRaw("contact_phone LIKE ? ESCAPE '|'", [$like])
                    ->orWhereRaw("company_name LIKE ? ESCAPE '|'", [$like]));
            })
            ->when($filters['status'] ?? null, fn (Builder $query, string $status) => $query->where('status', $status))
            ->when($filters['payment_method'] ?? null, fn (Builder $query, string $method) => $query->where('payment_method', $method))
            ->orderBy($filters['sort'] ?? 'created_at', $filters['direction'] ?? 'desc')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Orders/Index', [
            'orders' => OrderListResource::collection($orders),
            'filters' => (object) $filters,
            'options' => [
                'statuses' => array_map(fn (OrderStatus $status): array => ['id' => $status->value, 'name' => $status->label()], OrderStatus::cases()),
                'methods' => array_map(fn (PaymentMethod $method): array => ['id' => $method->value, 'name' => $method->label()], PaymentMethod::cases()),
            ],
        ]);
    }

    public function show(Order $order): Response
    {
        Gate::authorize('view', $order);

        $order->load(['items', 'payments.media', 'history.user', 'invoice', 'creditNote']);

        return Inertia::render('Orders/Show', [
            'order' => OrderDetailResource::make($order)->resolve(),
        ]);
    }
}
