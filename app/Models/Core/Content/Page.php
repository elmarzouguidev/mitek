<?php

namespace App\Models\Core\Content;

use App\Enums\PageKey;
use App\Policies\PagePolicy;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

#[UsePolicy(PagePolicy::class)]
class Page extends Model
{
    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return [
            'key' => PageKey::class,
            'is_published' => 'boolean',
        ];
    }

    /**
     * @param  Builder<Page>  $query
     * @return Builder<Page>
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }
}
