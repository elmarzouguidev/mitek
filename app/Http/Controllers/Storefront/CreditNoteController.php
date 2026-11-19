<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Core\Order\Order;
use App\Services\Core\Invoice\CreditNotePdf;
use Illuminate\Http\Response;

/**
 * Credit note PDF, behind the same access rules as the order page (see EnsureOrderAccess).
 */
class CreditNoteController extends Controller
{
    public function show(Order $order, CreditNotePdf $pdf): Response
    {
        $creditNote = $order->creditNote()->firstOrFail();

        return response($pdf->contents($creditNote), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$pdf->filename($creditNote).'"',
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, max-age=0, must-revalidate',
        ]);
    }
}
