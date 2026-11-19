<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();

        return view('account.index', [
            'orders' => $user->orders()->limit(3)->get(),
            'quotes' => $user->quoteRequests()->limit(3)->get(),
        ]);
    }
}
