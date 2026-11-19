<?php

namespace App\Listeners;

use App\Events\OrderPlaced;
use App\Mail\OrderReceivedMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Mail;

class SendOrderReceivedEmail implements ShouldQueue
{
    public bool $afterCommit = true;

    public function handle(OrderPlaced $event): void
    {
        Mail::to($event->order->contact_email)->send(new OrderReceivedMail($event->order));
    }
}
