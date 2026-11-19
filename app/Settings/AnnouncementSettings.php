<?php

namespace App\Settings;

use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Spatie\LaravelSettings\Settings;

/**
 * The optional dismissible bar above the header: text, link and an active period.
 */
class AnnouncementSettings extends Settings
{
    public bool $is_active;

    public ?string $text;

    public ?string $link_url;

    public ?string $link_label;

    /** Local date-time as typed in the back office (Y-m-d\TH:i), in the application timezone. */
    public ?string $starts_at;

    public ?string $ends_at;

    public bool $dismissible;

    public static function group(): string
    {
        return 'announcement';
    }

    public function isVisibleAt(CarbonInterface $now): bool
    {
        if (! $this->is_active || blank($this->text)) {
            return false;
        }

        if (filled($this->starts_at) && $now->lt(Carbon::parse($this->starts_at, config('app.timezone')))) {
            return false;
        }

        return ! (filled($this->ends_at) && $now->gt(Carbon::parse($this->ends_at, config('app.timezone'))));
    }

    public function isVisible(): bool
    {
        return $this->isVisibleAt(now());
    }
}
