<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Core\Order\Order;
use App\Services\Core\Invoice\InvoicePdf;
use Illuminate\Http\Response;

/**
 * Invoice PDF of an order, behind the same access rules as the order page (see EnsureOrderAccess).
 */
class InvoiceController extends Controller
{
    public function show(Order $order, InvoicePdf $pdf): Response
    {
        $invoice = $order->invoice()->firstOrFail();

        return response($pdf->contents($invoice), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$pdf->filename($invoice).'"',
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, max-age=0, must-revalidate',
        ]);
    }
}
