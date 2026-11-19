<?php

namespace App\Listeners;

use App\Events\QuoteReplied;
use App\Mail\QuoteRepliedMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Mail;

class SendQuoteRepliedEmail implements ShouldQueue
{
    public bool $afterCommit = true;

    public function handle(QuoteReplied $event): void
    {
        Mail::to($event->quote->contact_email)->send(new QuoteRepliedMail($event->quote));
    }
}
