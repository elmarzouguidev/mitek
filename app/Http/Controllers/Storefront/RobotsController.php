<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use Illuminate\Http\Response;

class RobotsController extends Controller
{
    /**
     * Private areas and non-content pages are blocked. Filtered category URLs are deliberately not
     * blocked: they carry `noindex,follow` and a canonical, which crawlers can only read if they may fetch them.
     */
    public function __invoke(): Response
    {
        $lines = ['User-agent: *'];

        foreach (config('seo.disallow') as $path) {
            $lines[] = 'Disallow: '.$path;
        }

        $lines[] = '';
        $lines[] = 'Sitemap: '.route('sitemap');

        return response(implode("\n", $lines)."\n", 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }
}
