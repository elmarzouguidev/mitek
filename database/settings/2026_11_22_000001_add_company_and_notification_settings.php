<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('general.tp_number', null);
        $this->migrator->add('general.cnss_number', null);
        $this->migrator->add('general.legal_form', 'SARL AU');
        $this->migrator->add('general.share_capital', null);
        $this->migrator->add('general.registered_address', null);
        $this->migrator->add('general.city', 'Casablanca');
        $this->migrator->add('general.admin_notification_email', 'contact@mitek.ma');
    }
};
