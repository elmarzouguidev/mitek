<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        foreach (['opening_hours', 'whatsapp_hours', 'facebook_url', 'instagram_url', 'linkedin_url', 'youtube_url', 'tiktok_url', 'cndp_number', 'warranty_text'] as $key) {
            $this->migrator->add('general.'.$key, null);
        }
    }
};
