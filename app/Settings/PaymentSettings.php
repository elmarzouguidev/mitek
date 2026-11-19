<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class PaymentSettings extends Settings
{
    public string $bank_name;

    public string $account_holder;

    /** TODO: RIB / IBAN not provided in the content file. */
    public ?string $rib;

    /** Maximum order total (TTC, in centimes) eligible for cash on delivery. */
    public int $cod_limit_cents;

    public string $cheque_payee;

    public string $bank_transfer_delay;

    public string $cash_deposit_delay;

    public string $cheque_delay;

    public static function group(): string
    {
        return 'payment';
    }
}
