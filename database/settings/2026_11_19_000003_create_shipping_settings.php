<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('shipping.dispatch_days', 2);
        $this->migrator->add('shipping.delivery_estimate', '24 à 72 h selon la ville');
        $this->migrator->add('shipping.own_carrier_fee_cents', 0);
    }
};
