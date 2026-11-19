<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Short internal email telling the team that something needs attention in the back office.
 */
class AdminNotificationMail extends Mailable
{
    use SerializesModels;

    /**
     * @param  array<int, string>  $lines
     */
    public function __construct(
        public string $headline,
        public array $lines,
        public string $url,
        public string $actionLabel,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: '[Mitek.ma] '.$this->headline);
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.admin-notification');
    }
}
