<?php

namespace App\Models\Core\Quote;

use App\Enums\QuoteStatus;
use App\Models\User\User;
use App\Policies\QuoteRequestPolicy;
use App\Traits\UuidGenerator;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[UsePolicy(QuoteRequestPolicy::class)]
class QuoteRequest extends Model
{
    use UuidGenerator;

    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return [
            'status' => QuoteStatus::class,
            'replied_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function repliedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'replied_by');
    }

    /**
     * @return HasMany<QuoteRequestItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(QuoteRequestItem::class);
    }
}
