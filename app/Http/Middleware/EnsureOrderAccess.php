<?php

namespace App\Http\Middleware;

use App\Models\Core\Order\Order;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Guests reach their order through a signed link; account holders through ownership (OrderPolicy).
 * Everything else gets a 403 (never a hint that the order exists).
 */
class EnsureOrderAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $order = $request->route('order');

        $allowed = $order instanceof Order
            && ($request->hasValidSignature() || ($request->user()?->can('view', $order) ?? false));

        abort_unless($allowed, 403);

        return $next($request);
    }
}
