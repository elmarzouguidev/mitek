<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class CatalogSettings extends Settings
{
    /**
     * A product also earns the "meilleure vente" badge automatically when it sold at least this many
     * units over the last 90 days. 0 disables the automatic badge: only the manual flag applies.
     */
    public int $best_seller_min_sales;

    public static function group(): string
    {
        return 'catalog';
    }
}
