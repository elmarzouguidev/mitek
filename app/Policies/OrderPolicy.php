<?php

namespace App\Policies;

use App\Models\Core\Order\Order;
use App\Models\User\User;

/**
 * Administrators manage every order; a customer only sees the orders linked to their account.
 */
class OrderPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, Order $order): bool
    {
        return $user->isAdmin() || ($order->user_id !== null && $order->user_id === $user->id);
    }

    public function update(User $user, ?Order $order = null): bool
    {
        return $user->isAdmin();
    }

    /**
     * Uploading a proof: the owner of the order, or an administrator on their behalf.
     */
    public function submitProof(User $user, Order $order): bool
    {
        return $this->view($user, $order);
    }
}
