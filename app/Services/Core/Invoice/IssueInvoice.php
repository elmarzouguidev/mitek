<?php

namespace App\Services\Core\Invoice;

use App\Models\Core\Invoice\Invoice;
use App\Models\Core\Order\Order;
use App\Settings\GeneralSettings;
use Illuminate\Support\Facades\DB;

/**
 * Issues the invoice of an order exactly once, numbered FA-YYYY-00001 without gaps inside a year.
 */
class IssueInvoice
{
    public function __construct(private readonly GeneralSettings $settings) {}

    public function handle(Order $order): Invoice
    {
        return DB::transaction(function () use ($order): Invoice {
            $existing = Invoice::where('order_id', $order->id)->first();

            if ($existing !== null) {
                return $existing;
            }

            $issuedAt = now();

            return Invoice::create([
                'order_id' => $order->id,
                'number' => $this->nextNumber($issuedAt->year),
                'issued_at' => $issuedAt,
                'seller' => $this->sellerSnapshot($this->settings),
            ]);
        });
    }

    /**
     * The year row is locked, so two concurrent orders can never receive the same number.
     */
    private function nextNumber(int $year): string
    {
        DB::table('invoice_sequences')->insertOrIgnore(['year' => $year, 'last_number' => 0]);

        $sequence = DB::table('invoice_sequences')->where('year', $year)->lockForUpdate()->first();
        $next = $sequence->last_number + 1;

        DB::table('invoice_sequences')->where('year', $year)->update(['last_number' => $next]);

        return sprintf('FA-%d-%05d', $year, $next);
    }

    /**
     * Seller details frozen on every fiscal document (invoice, credit note).
     *
     * @return array<string, mixed>
     */
    public static function sellerSnapshot(GeneralSettings $settings): array
    {
        return [
            'name' => $settings->legal_name,
            'site' => $settings->site_name,
            'legal_form' => $settings->legal_form,
            'share_capital' => $settings->share_capital,
            'address' => $settings->address,
            'registered_address' => $settings->registered_address,
            'email' => $settings->email,
            'phone' => $settings->phone,
            'ice' => $settings->ice,
            'if' => $settings->if_number,
            'rc' => $settings->rc_number,
            'tp' => $settings->tp_number,
            'cnss' => $settings->cnss_number,
            'vat_rate' => $settings->vat_rate,
        ];
    }
}
