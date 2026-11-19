<?php

namespace App\Mail;

use App\Models\Core\Quote\QuoteRequest;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\URL;

class QuoteRepliedMail extends Mailable
{
    use SerializesModels;

    public function __construct(public QuoteRequest $quote) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: __('Réponse à votre demande de devis :reference', ['reference' => $this->quote->reference]));
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.quotes.replied', with: [
            'quoteUrl' => URL::signedRoute('quotes.show', $this->quote),
            'items' => $this->quote->items()->get(),
        ]);
    }
}
