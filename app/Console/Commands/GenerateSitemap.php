<?php

namespace App\Console\Commands;

use App\Services\Seo\SitemapGenerator;
use Illuminate\Console\Command;

class GenerateSitemap extends Command
{
    protected $signature = 'sitemap:generate';

    protected $description = 'Regenerate sitemap.xml from the public content';

    public function handle(SitemapGenerator $generator): int
    {
        $path = $generator->write();

        $this->info("Sitemap written to {$path}");

        return self::SUCCESS;
    }
}
