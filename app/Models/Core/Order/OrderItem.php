<?php

namespace App\Models\Core\Order;

use App\Casts\MoneyCast;
use App\Models\Core\Product\Product;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A line of an order. Name, reference and prices are snapshots: they never follow later catalogue changes.
 */
class OrderItem extends Model
{
    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'vat_rate' => 'integer',
            'weight_grams' => 'integer',
            'unit_price_ht' => MoneyCast::class,
            'line_ht' => MoneyCast::class,
            'line_vat' => MoneyCast::class,
            'line_ttc' => MoneyCast::class,
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
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class)->withTrashed();
    }
}
