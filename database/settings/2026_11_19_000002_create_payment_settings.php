<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('payment.bank_name', 'Banque Populaire');
        $this->migrator->add('payment.account_holder', 'STE MITEK SARL AU');
        $this->migrator->add('payment.rib', null);
        $this->migrator->add('payment.cod_limit_cents', 200000);
        $this->migrator->add('payment.cheque_payee', 'STE MITEK SARL AU');
        $this->migrator->add('payment.bank_transfer_delay', '48 à 72 heures');
        $this->migrator->add('payment.cash_deposit_delay', 'environ 24 heures');
        $this->migrator->add('payment.cheque_delay', '8 à 20 jours');
    }
};
