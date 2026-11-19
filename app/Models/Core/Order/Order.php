<?php

namespace App\Models\Core\Order;

use App\Casts\MoneyCast;
use App\Enums\CarrierMode;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Models\Core\Invoice\Invoice;
use App\Models\Core\Shipping\ShippingZone;
use App\Models\User\User;
use App\Policies\OrderPolicy;
use App\Traits\UuidGenerator;
use Database\Factories\Core\Order\OrderFactory;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[UsePolicy(OrderPolicy::class)]
class Order extends Model
{
    /** @use HasFactory<OrderFactory> */
    use HasFactory, UuidGenerator;

    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return [
            'status' => OrderStatus::class,
            'payment_method' => PaymentMethod::class,
            'carrier_mode' => CarrierMode::class,
            'total_weight_grams' => 'integer',
            'subtotal_ht' => MoneyCast::class,
            'shipping_ht' => MoneyCast::class,
            'vat_total' => MoneyCast::class,
            'total_ttc' => MoneyCast::class,
            'paid_at' => 'datetime',
            'shipped_at' => 'datetime',
            'delivered_at' => 'datetime',
            'cancelled_at' => 'datetime',
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
     * @return BelongsTo<ShippingZone, $this>
     */
    public function shippingZone(): BelongsTo
    {
        return $this->belongsTo(ShippingZone::class);
    }

    /**
     * @return HasOne<Invoice, $this>
     */
    public function invoice(): HasOne
    {
        return $this->hasOne(Invoice::class);
    }

    /**
     * @return HasMany<OrderItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * @return HasMany<OrderPayment, $this>
     */
    public function payments(): HasMany
    {
        return $this->hasMany(OrderPayment::class)->latest('id');
    }

    /**
     * @return HasMany<OrderStatusHistory, $this>
     */
    public function history(): HasMany
    {
        return $this->hasMany(OrderStatusHistory::class)->orderBy('id');
    }

    public function isCashOnDelivery(): bool
    {
        return $this->payment_method === PaymentMethod::CashOnDelivery;
    }

    /**
     * The customer can upload a proof while the order is waiting for payment.
     */
    public function acceptsProof(): bool
    {
        return $this->payment_method->requiresProof() && $this->status === OrderStatus::PendingPayment;
    }

    /**
     * @return array<int, OrderStatus>
     */
    public function allowedNextStatuses(): array
    {
        return $this->status->allowedNext($this->payment_method);
    }
}
