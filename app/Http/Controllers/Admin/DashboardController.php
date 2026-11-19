<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Core\Product\Product;
use App\Models\User\User;
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
            ],
            'lowStockProducts' => $lowStock
                ->orderBy('stock_quantity')
                ->orderBy('name')
                ->limit(self::LOW_STOCK_PREVIEW)
                ->get(['id', 'reference', 'name', 'stock_quantity', 'low_stock_threshold']),
        ]);
    }
}
