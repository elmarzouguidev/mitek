<?php

namespace App\Models\Core\Order;

use App\Casts\MoneyCast;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\User\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

/**
 * A payment submission: the proof files (transfer slip, deposit slip, cheque copy) are kept on the
 * private disk and only served through authorised routes.
 */
class OrderPayment extends Model implements HasMedia
{
    use InteractsWithMedia;

    public const PROOF = 'proof';

    public const PROOF_DISK = 'local';

    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return [
            'method' => PaymentMethod::class,
            'status' => PaymentStatus::class,
            'amount' => MoneyCast::class,
            'reviewed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Order, $this>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection(self::PROOF)->useDisk(self::PROOF_DISK);
    }
}
