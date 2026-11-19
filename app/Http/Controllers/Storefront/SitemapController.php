<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Services\Seo\SitemapGenerator;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\File;

class SitemapController extends Controller
{
    /**
     * Serves the generated file, building it on the first request.
     */
    public function __invoke(SitemapGenerator $generator): Response
    {
        $path = config('seo.sitemap_path');

        if (! File::exists($path)) {
            $generator->write();
        }

        return response(File::get($path), 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }
}
