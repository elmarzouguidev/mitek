<?php

namespace App\Models\Core\Service;

use App\Policies\ServicePolicy;
use App\Traits\HasSlug;
use App\Traits\UuidGenerator;
use Database\Factories\Core\Service\ServiceFactory;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * An IT service Mitek sells. Services are never put in the cart: each one has a page with a request form.
 */
#[UsePolicy(ServicePolicy::class)]
class Service extends Model
{
    /** @use HasFactory<ServiceFactory> */
    use HasFactory, HasSlug, UuidGenerator;

    protected string $slugName = 'title';

    /**
     * Icon keys understood by the `service-icon` Blade component.
     *
     * @var array<int, string>
     */
    public const ICONS = ['wrench', 'network', 'shield', 'cloud', 'database', 'desktop', 'chip', 'clipboard', 'plus'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'bullets' => 'array',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /**
     * @return HasMany<ServiceRequest, $this>
     */
    public function requests(): HasMany
    {
        return $this->hasMany(ServiceRequest::class);
    }

    /**
     * @param  Builder<Service>  $query
     * @return Builder<Service>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * @param  Builder<Service>  $query
     * @return Builder<Service>
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('title');
    }
}
