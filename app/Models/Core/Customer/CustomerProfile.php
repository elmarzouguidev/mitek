<?php

namespace App\Models\Core\Customer;

use App\Models\User\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Phone and company details (ICE, IF, RC) a customer keeps on their account to speed up checkout.
 */
class CustomerProfile extends Model
{
    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isCompany(): bool
    {
        return filled($this->company_name);
    }
}
