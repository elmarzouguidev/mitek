<?php

namespace App\Jobs;

use App\Services\Seo\SitemapGenerator;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Rebuilds sitemap.xml after public content is published or removed. Unique, so a burst of edits
 * results in a single regeneration.
 */
class RegenerateSitemap implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $uniqueFor = 120;

    public function __construct()
    {
        $this->afterCommit();
    }

    public function handle(SitemapGenerator $generator): void
    {
        $generator->write();
    }
}
