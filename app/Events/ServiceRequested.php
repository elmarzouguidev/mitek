<?php

namespace App\Events;

use App\Models\Core\Service\ServiceRequest;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ServiceRequested
{
    use Dispatchable, SerializesModels;

    public function __construct(public ServiceRequest $request) {}
}
