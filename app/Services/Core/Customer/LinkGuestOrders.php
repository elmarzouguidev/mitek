<?php

namespace App\Services\Core\Customer;

use App\Models\Core\Order\Order;
use App\Models\User\User;

/**
 * Attaches the orders a person placed as a guest to their account, but only once the account's
 * email address is verified: an unverified address proves nothing, and linking would expose the
 * orders (names, addresses, invoices) of whoever owns that address.
 */
class LinkGuestOrders
{
    /**
     * @return int number of orders linked
     */
    public function handle(User $user): int
    {
        if (! $user->hasVerifiedEmail()) {
            return 0;
        }

        return Order::query()
            ->whereNull('user_id')
            ->whereRaw('LOWER(contact_email) = ?', [mb_strtolower($user->email)])
            ->update(['user_id' => $user->id]);
    }
}
