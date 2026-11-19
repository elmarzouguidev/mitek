<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function index(Request $request): View
    {
        return view('account.orders', [
            'orders' => $request->user()->orders()->with('invoice')->paginate(10),
        ]);
    }
}
