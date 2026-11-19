<?php

namespace App\Mail;

use App\Models\Core\Service\ServiceRequest;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ServiceRequestReceivedMail extends Mailable
{
    use SerializesModels;

    public function __construct(public ServiceRequest $serviceRequest) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: __('Votre demande :reference a bien été reçue', ['reference' => $this->serviceRequest->reference]));
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.services.received');
    }
}
