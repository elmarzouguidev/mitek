<?php

namespace App\Policies;

use App\Models\Core\Customer\Address;
use App\Models\User\User;

/**
 * A customer manages their own addresses only.
 */
class AddressPolicy
{
    public function update(User $user, Address $address): bool
    {
        return $address->user_id === $user->id;
    }

    public function delete(User $user, Address $address): bool
    {
        return $address->user_id === $user->id;
    }
}
