<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('general.ice', null);
        $this->migrator->add('general.if_number', null);
        $this->migrator->add('general.rc_number', null);
    }
};
