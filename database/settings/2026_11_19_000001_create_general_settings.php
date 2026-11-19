<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('general.site_name', 'Mitek.ma');
        $this->migrator->add('general.legal_name', 'STE MITEK SARL AU');
        $this->migrator->add('general.email', 'contact@mitek.ma');
        $this->migrator->add('general.phone', null);
        $this->migrator->add('general.whatsapp', null);
        $this->migrator->add('general.address', 'Casablanca, Maroc');
        $this->migrator->add('general.maps_embed_url', null);
        $this->migrator->add('general.vat_rate', 20);
    }
};
