<?php

use App\Support\WhatsApp;
use Illuminate\Support\Facades\Route;

if (! function_exists('nav_feature')) {
    /**
     * Whether a navigation feature exists yet. A header or footer link is rendered only when its page
     * exists, so the site never shows a dead link (see config/features.php).
     */
    function nav_feature(string $feature): bool
    {
        $route = config('features.'.$feature);

        return is_string($route) && Route::has($route);
    }
}

if (! function_exists('whatsapp_url')) {
    /**
     * wa.me link from the settings (optionally with a prefilled message); null while the number is empty.
     */
    function whatsapp_url(?string $message = null): ?string
    {
        return app(WhatsApp::class)->link($message);
    }
}
