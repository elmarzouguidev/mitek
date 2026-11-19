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
    public function __invoke(): Response
    {
        return Inertia::render('Dashboard', [
            'stats' => [
                'customers' => User::role(UserRole::Customer->value)->count(),
                'products' => Product::count(),
            ],
        ]);
    }
}
