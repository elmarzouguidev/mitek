<?php

namespace App\Policies;

use App\Models\Core\Quote\QuoteRequest;
use App\Models\User\User;

class QuoteRequestPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, QuoteRequest $quote): bool
    {
        return $user->isAdmin() || ($quote->user_id !== null && $quote->user_id === $user->id);
    }

    public function update(User $user, ?QuoteRequest $quote = null): bool
    {
        return $user->isAdmin();
    }
}
