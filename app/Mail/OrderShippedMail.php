<?php

namespace App\Mail;

use App\Models\Core\Order\Order;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\URL;

class OrderShippedMail extends Mailable
{
    use SerializesModels;

    public function __construct(public Order $order) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: __('Votre commande :reference est expédiée', ['reference' => $this->order->reference]));
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.orders.shipped', with: [
            'orderUrl' => URL::signedRoute('orders.show', $this->order),
        ]);
    }
}
