<?php

namespace App\Models\Core\Invoice;

use App\Models\Core\Order\Order;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Sequential invoice of an order. The seller details are a snapshot taken when the invoice is issued,
 * so later changes to the company settings never alter an issued invoice. The PDF is rendered on
 * first use and cached on the private disk.
 */
class Invoice extends Model
{
    public const DISK = 'local';

    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return [
            'issued_at' => 'datetime',
            'seller' => 'array',
        ];
    }

    /**
     * @return BelongsTo<Order, $this>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
