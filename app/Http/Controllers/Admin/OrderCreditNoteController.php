<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Core\Order\Order;
use App\Services\Core\Invoice\CreditNotePdf;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class OrderCreditNoteController extends Controller
{
    public function show(Order $order, CreditNotePdf $pdf): Response
    {
        Gate::authorize('view', $order);

        $creditNote = $order->creditNote()->firstOrFail();

        return response($pdf->contents($creditNote), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$pdf->filename($creditNote).'"',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
