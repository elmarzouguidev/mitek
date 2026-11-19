<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Core\Order\Order;
use App\Services\Core\Invoice\InvoicePdf;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class OrderInvoiceController extends Controller
{
    public function show(Order $order, InvoicePdf $pdf): Response
    {
        Gate::authorize('view', $order);

        $invoice = $order->invoice()->firstOrFail();

        return response($pdf->contents($invoice), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$pdf->filename($invoice).'"',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
