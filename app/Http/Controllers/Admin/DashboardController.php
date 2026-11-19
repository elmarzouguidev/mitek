<?php

namespace App\Http\Controllers\Admin;

use App\Enums\OrderStatus;
use App\Enums\QuoteStatus;
use App\Enums\ServiceRequestStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Core\Content\ContactMessage;
use App\Models\Core\Order\Order;
use App\Models\Core\Product\Product;
use App\Models\Core\Quote\QuoteRequest;
use App\Models\Core\Service\ServiceRequest;
use App\Models\User\User;
use App\Support\Money;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    private const LOW_STOCK_PREVIEW = 5;

    public function __invoke(): Response
    {
        $lowStock = Product::query()->where('is_active', true)->lowStock();

        return Inertia::render('Dashboard', [
            'stats' => [
                'customers' => User::role(UserRole::Customer->value)->count(),
                'products' => Product::count(),
                'low_stock' => (clone $lowStock)->count(),
                'new_quotes' => QuoteRequest::where('status', QuoteStatus::New)->count(),
                'new_service_requests' => ServiceRequest::where('status', ServiceRequestStatus::New)->count(),
                'unread_messages' => ContactMessage::unread()->count(),
                'awaiting_validation' => Order::where('status', OrderStatus::PaymentSubmitted)->count(),
                'to_process' => Order::whereIn('status', [OrderStatus::PendingPayment, OrderStatus::Paid, OrderStatus::Processing])
                    ->where(fn ($query) => $query->where('status', '!=', OrderStatus::PendingPayment)->orWhere('payment_method', 'cod'))
                    ->count(),
                'revenue_30_days' => Money::fromCents((int) Order::whereIn('status', OrderStatus::revenueStatuses())
                    ->where('created_at', '>=', now()->subDays(30))
                    ->sum('total_ttc'))->format(),
            ],
            'lowStockProducts' => $lowStock
                ->orderBy('stock_quantity')
                ->orderBy('name')
                ->limit(self::LOW_STOCK_PREVIEW)
                ->get(['id', 'reference', 'name', 'stock_quantity', 'low_stock_threshold']),
        ]);
    }
}
