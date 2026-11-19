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
                'seller' => [
                    'name' => $this->settings->legal_name,
                    'site' => $this->settings->site_name,
                    'address' => $this->settings->address,
                    'email' => $this->settings->email,
                    'phone' => $this->settings->phone,
                    'ice' => $this->settings->ice,
                    'if' => $this->settings->if_number,
                    'rc' => $this->settings->rc_number,
                    'vat_rate' => $this->settings->vat_rate,
                ],
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
}
