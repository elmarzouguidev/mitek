<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Http\Requests\PaymentProofRequest;
use App\Models\Core\Order\Order;
use App\Services\Core\Order\SubmitPaymentProof;
use App\Settings\GeneralSettings;
use App\Settings\PaymentSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\URL;
use Illuminate\View\View;

/**
 * Order page reached through a signed link (guests) or from the account (owners); see EnsureOrderAccess.
 */
class OrderController extends Controller
{
    public function show(Order $order, PaymentSettings $payment, GeneralSettings $general): View
    {
        $order->load(['items', 'payments.media', 'history', 'invoice', 'creditNote']);

        return view('orders.show', [
            'order' => $order,
            'payment' => $payment,
            'general' => $general,
            'proofUrl' => URL::signedRoute('orders.proof.store', $order),
            'creditNoteUrl' => $order->creditNote ? URL::signedRoute('orders.credit-note', $order) : null,
            'invoiceUrl' => $order->invoice ? URL::signedRoute('orders.invoice', $order) : null,
        ]);
    }

    public function storeProof(PaymentProofRequest $request, Order $order, SubmitPaymentProof $submit): RedirectResponse
    {
        $submit->handle($order, $request->file('proofs'), $request->user());

        return redirect()
            ->to(URL::signedRoute('orders.show', $order))
            ->with('success', __('orders.proof_sent'));
    }
}
