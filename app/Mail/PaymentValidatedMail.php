<?php

namespace App\Mail;

use App\Models\Core\Order\Order;
use App\Services\Core\Invoice\InvoicePdf;
use Illuminate\Mail\Attachment;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\URL;

/**
 * Sent when the payment is validated (or a cash on delivery order is confirmed), with the invoice attached.
 */
class PaymentValidatedMail extends Mailable
{
    use SerializesModels;

    public function __construct(public Order $order) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: __($this->order->isCashOnDelivery() ? 'Commande :reference confirmée' : 'Paiement validé - commande :reference', ['reference' => $this->order->reference]));
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.orders.payment-validated', with: [
            'orderUrl' => URL::signedRoute('orders.show', $this->order),
            'isCod' => $this->order->isCashOnDelivery(),
        ]);
    }

    /**
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        $invoice = $this->order->invoice()->first();

        if ($invoice === null) {
            return [];
        }

        $pdf = app(InvoicePdf::class);

        return [
            Attachment::fromData(fn (): string => $pdf->contents($invoice), $pdf->filename($invoice))->withMime('application/pdf'),
        ];
    }
}
