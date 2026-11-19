<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\IllegalStatusTransition;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\RejectPaymentRequest;
use App\Http\Requests\PaymentProofRequest;
use App\Models\Core\Order\Order;
use App\Models\Core\Order\OrderPayment;
use App\Services\Core\Order\ReviewPayment;
use App\Services\Core\Order\SubmitPaymentProof;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class OrderPaymentController extends Controller
{
    public function validatePayment(Request $request, OrderPayment $payment, ReviewPayment $review): RedirectResponse
    {
        Gate::authorize('update', $payment->order()->firstOrFail());

        try {
            $review->validate($payment, $request->user(), $request->string('note')->toString() ?: null);
        } catch (IllegalStatusTransition $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', __('admin.orders.payment_validated'));
    }

    public function rejectPayment(RejectPaymentRequest $request, OrderPayment $payment, ReviewPayment $review): RedirectResponse
    {
        Gate::authorize('update', $payment->order()->firstOrFail());

        try {
            $review->reject($payment, $request->user(), $request->validated('note'));
        } catch (IllegalStatusTransition $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', __('admin.orders.payment_rejected'));
    }

    /**
     * An administrator attaches a proof on behalf of a customer (e.g. received by email or WhatsApp).
     */
    public function storeProof(PaymentProofRequest $request, Order $order, SubmitPaymentProof $submit): RedirectResponse
    {
        Gate::authorize('update', $order);

        $submit->handle($order, $request->file('proofs'), $request->user());

        return back()->with('success', __('admin.orders.proof_added'));
    }

    /**
     * Proofs live on the private disk; they are only streamed to administrators.
     */
    public function showProof(OrderPayment $payment, int $media): StreamedResponse
    {
        Gate::authorize('view', $payment->order()->firstOrFail());

        $file = $payment->getMedia(OrderPayment::PROOF)->firstWhere('id', $media) ?? abort(404);

        return Storage::disk($file->disk)->response($file->getPathRelativeToRoot(), $file->file_name, [
            'Content-Type' => $file->mime_type,
            'X-Content-Type-Options' => 'nosniff',
            'Content-Security-Policy' => "default-src 'none'; img-src 'self' data:; style-src 'unsafe-inline'",
        ]);
    }
}
