<?php

namespace App\Listeners;

use App\Events\ServiceRequested;
use App\Mail\ServiceRequestReceivedMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Mail;

class SendServiceRequestConfirmation implements ShouldQueue
{
    public bool $afterCommit = true;

    public function handle(ServiceRequested $event): void
    {
        Mail::to($event->request->email)->send(new ServiceRequestReceivedMail($event->request));
    }
}
