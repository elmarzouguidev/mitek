<?php

namespace App\Models\User;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use App\Enums\UserRole;
use App\Models\Core\Customer\Address;
use App\Models\Core\Customer\CustomerProfile;
use App\Models\Core\Order\Order;
use App\Models\Core\Quote\QuoteRequest;
use App\Traits\HasTrustedTwoFactorDevices;
use App\Traits\HasTwoFactorAuthentication;
use App\Traits\UuidGenerator;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token', 'two_factor_secret', 'two_factor_recovery_codes', 'two_factor_confirmed_at'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, HasTrustedTwoFactorDevices, HasTwoFactorAuthentication, Notifiable, UuidGenerator;

    /**
     * @return HasOne<CustomerProfile, $this>
     */
    public function profile(): HasOne
    {
        return $this->hasOne(CustomerProfile::class);
    }

    /**
     * @return HasMany<Address, $this>
     */
    public function addresses(): HasMany
    {
        return $this->hasMany(Address::class)->orderByDesc('is_default')->orderBy('label');
    }

    /**
     * @return HasMany<Order, $this>
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class)->latest('id');
    }

    /**
     * @return HasMany<QuoteRequest, $this>
     */
    public function quoteRequests(): HasMany
    {
        return $this->hasMany(QuoteRequest::class)->latest('id');
    }

    public function isAdmin(): bool
    {
        return $this->hasRole(UserRole::Admin->value);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_secret' => 'encrypted',
        ];
    }
}
