<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('announcement.is_active', false);
        $this->migrator->add('announcement.text', null);
        $this->migrator->add('announcement.link_url', null);
        $this->migrator->add('announcement.link_label', null);
        $this->migrator->add('announcement.starts_at', null);
        $this->migrator->add('announcement.ends_at', null);
        $this->migrator->add('announcement.dismissible', true);
    }
};
