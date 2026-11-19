<?php

namespace App\Listeners;

use App\Services\Core\Customer\LinkGuestOrders;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Events\Verified;

class LinkGuestOrdersToAccount
{
    public function __construct(private readonly LinkGuestOrders $linker) {}

    /**
     * An account created with an already verified email (e.g. by an administrator).
     */
    public function handleRegistered(Registered $event): void
    {
        $this->linker->handle($event->user);
    }

    public function handleVerified(Verified $event): void
    {
        $this->linker->handle($event->user);
    }
}
