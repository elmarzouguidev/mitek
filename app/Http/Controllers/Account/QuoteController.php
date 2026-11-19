<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

class QuoteController extends Controller
{
    public function index(Request $request): View
    {
        return view('account.quotes', [
            'quotes' => $request->user()->quoteRequests()->withCount('items')->paginate(10),
        ]);
    }
}
