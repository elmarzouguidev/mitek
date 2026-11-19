<?php

namespace App\Http\Middleware;

use App\Models\Core\Quote\QuoteRequest;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Quote requests follow the order rules: a signed link for guests, ownership (QuoteRequestPolicy) for account holders.
 */
class EnsureQuoteAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $quote = $request->route('quote');

        $allowed = $quote instanceof QuoteRequest
            && ($request->hasValidSignature() || ($request->user()?->can('view', $quote) ?? false));

        abort_unless($allowed, 403);

        return $next($request);
    }
}
