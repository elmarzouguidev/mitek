<?php

namespace App\Traits;

use App\Models\Tools\SeoMetadata;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Support\Arr;

trait Seoable
{
    public function seo(): MorphOne
    {
        return $this->morphOne(SeoMetadata::class, 'seoable');
    }

    /**
     * Stores the validated SEO fields, defaulting robots to "index,follow".
     *
     * @param  array<string, mixed>|null  $seo
     */
    public function saveSeo(?array $seo): void
    {
        $metadata = Arr::only($seo ?? [], ['meta_title', 'meta_description', 'canonical_url', 'robots', 'og_title', 'og_description']);
        $metadata['robots'] = $metadata['robots'] ?? 'index,follow';
        $this->seo()->updateOrCreate([], $metadata);
    }
}
