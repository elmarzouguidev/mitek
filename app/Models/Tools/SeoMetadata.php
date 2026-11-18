<?php

namespace App\Models\Tools;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class SeoMetadata extends Model
{
    use HasFactory;

    protected $table = 'seo_metadata';

    protected $fillable = ['meta_title', 'meta_description', 'canonical_url', 'robots', 'og_title', 'og_description'];

    public function seoable(): MorphTo
    {
        return $this->morphTo();
    }
}
