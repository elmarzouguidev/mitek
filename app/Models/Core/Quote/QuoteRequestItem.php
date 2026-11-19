<?php

namespace App\Models\Core\Quote;

use App\Casts\MoneyCast;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A requested product. The unit price is the indicative HT price (quantity tier applied) at request time.
 */
class QuoteRequestItem extends Model
{
    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'unit_price_ht' => MoneyCast::class,
        ];
    }

    /**
     * @return BelongsTo<QuoteRequest, $this>
     */
    public function quoteRequest(): BelongsTo
    {
        return $this->belongsTo(QuoteRequest::class);
    }
}
