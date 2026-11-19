<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('shipping.working_days', [1, 2, 3, 4, 5, 6]);
        $this->migrator->add('shipping.holidays', []);
    }
};
