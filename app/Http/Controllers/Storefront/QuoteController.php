<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Http\Requests\QuoteRequestForm;
use App\Models\Core\Quote\QuoteRequest;
use App\Services\Core\Cart\CartService;
use App\Services\Core\Quote\SubmitQuoteRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Illuminate\View\View;

class QuoteController extends Controller
{
    public function create(Request $request, CartService $cart): View|RedirectResponse
    {
        $summary = $cart->summary();

        if ($summary->isEmpty()) {
            return redirect()->route('cart.show');
        }

        $user = $request->user();
        $profile = $user?->profile()->first();

        return view('quotes.create', [
            'summary' => $summary,
            'prefill' => [
                'contact_name' => $user?->name,
                'contact_email' => $user?->email,
                'contact_phone' => $profile?->phone,
                'company_name' => $profile?->company_name,
                'ice' => $profile?->ice,
            ],
        ]);
    }

    public function store(QuoteRequestForm $request, SubmitQuoteRequest $submit): RedirectResponse
    {
        $quote = $submit->handle($request->validated(), $request->user());

        return redirect()
            ->to(URL::signedRoute('quotes.show', $quote))
            ->with('success', __('quotes.sent', ['reference' => $quote->reference]));
    }

    public function show(QuoteRequest $quote): View
    {
        $quote->load('items');

        return view('quotes.show', ['quote' => $quote]);
    }
}
