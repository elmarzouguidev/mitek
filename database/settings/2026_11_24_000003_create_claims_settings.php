<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        foreach (['quote_within_24h', 'dedicated_contact', 'admin_documents_on_request', 'proforma_available', 'pickup_casablanca', 'show_warranty_lines'] as $claim) {
            $this->migrator->add('claims.'.$claim, false);
        }
    }
};
