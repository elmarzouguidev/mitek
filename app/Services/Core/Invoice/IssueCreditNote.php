<?php

namespace App\Services\Core\Invoice;

use App\Models\Core\Invoice\CreditNote;
use App\Models\Core\Order\Order;
use App\Settings\GeneralSettings;
use Illuminate\Support\Facades\DB;

/**
 * Issues the credit note AV-YYYY-00001 of a cancelled, invoiced order: the full invoice is reversed.
 * Numbering is gapless per year and safe under concurrency (the year row is locked).
 */
class IssueCreditNote
{
    public function __construct(private readonly GeneralSettings $settings) {}

    /**
     * Null when the order was never invoiced (nothing to reverse).
     */
    public function handle(Order $order): ?CreditNote
    {
        return DB::transaction(function () use ($order): ?CreditNote {
            $invoice = $order->invoice()->first();

            if ($invoice === null) {
                return null;
            }

            $existing = CreditNote::where('order_id', $order->id)->first();

            if ($existing !== null) {
                return $existing;
            }

            $issuedAt = now();

            return CreditNote::create([
                'order_id' => $order->id,
                'invoice_id' => $invoice->id,
                'number' => $this->nextNumber($issuedAt->year),
                'issued_at' => $issuedAt,
                'seller' => IssueInvoice::sellerSnapshot($this->settings),
                'subtotal_ht' => -($order->subtotal_ht->cents + $order->shipping_ht->cents),
                'vat_total' => -$order->vat_total->cents,
                'total_ttc' => -$order->total_ttc->cents,
            ]);
        });
    }

    private function nextNumber(int $year): string
    {
        DB::table('credit_note_sequences')->insertOrIgnore(['year' => $year, 'last_number' => 0]);

        $sequence = DB::table('credit_note_sequences')->where('year', $year)->lockForUpdate()->first();
        $next = $sequence->last_number + 1;

        DB::table('credit_note_sequences')->where('year', $year)->update(['last_number' => $next]);

        return sprintf('AV-%d-%05d', $year, $next);
    }
}
