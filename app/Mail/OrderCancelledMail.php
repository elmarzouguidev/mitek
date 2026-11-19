<?php

namespace App\Mail;

use App\Models\Core\Order\Order;
use App\Services\Core\Invoice\CreditNotePdf;
use Illuminate\Mail\Attachment;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\URL;

class OrderCancelledMail extends Mailable
{
    use SerializesModels;

    public function __construct(public Order $order) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: __('Commande :reference annulée', ['reference' => $this->order->reference]));
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.orders.cancelled', with: [
            'orderUrl' => URL::signedRoute('orders.show', $this->order),
            'creditNote' => $this->order->creditNote()->first(),
        ]);
    }

    /**
     * The credit note is attached when the cancelled order had been invoiced.
     *
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        $creditNote = $this->order->creditNote()->first();

        if ($creditNote === null) {
            return [];
        }

        $pdf = app(CreditNotePdf::class);

        return [
            Attachment::fromData(fn (): string => $pdf->contents($creditNote), $pdf->filename($creditNote))->withMime('application/pdf'),
        ];
    }
}
