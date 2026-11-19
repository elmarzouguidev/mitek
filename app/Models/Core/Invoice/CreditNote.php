<?php

namespace App\Models\Core\Invoice;

use App\Casts\MoneyCast;
use App\Models\Concerns\IsIssuedDocument;
use App\Models\Core\Order\Order;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Credit note (avoir) issued when an invoiced order is cancelled. It reverses the invoice with
 * negative amounts and is immutable once issued.
 */
class CreditNote extends Model
{
    use IsIssuedDocument;

    public const DISK = 'local';

    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return [
            'issued_at' => 'datetime',
            'seller' => 'array',
            'subtotal_ht' => MoneyCast::class,
            'vat_total' => MoneyCast::class,
            'total_ttc' => MoneyCast::class,
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
     * @return BelongsTo<Invoice, $this>
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }
}
