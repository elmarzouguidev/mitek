<?php

namespace App\Models\Core\Product;

use App\Policies\CategoryPolicy;
use App\Services\Core\Product\CatalogMenu;
use App\Traits\HasSlug;
use App\Traits\UuidGenerator;
use Database\Factories\Core\Product\CategoryFactory;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

#[UsePolicy(CategoryPolicy::class)]
class Category extends Model implements HasMedia
{
    /** @use HasFactory<CategoryFactory> */
    use HasFactory, HasSlug, InteractsWithMedia, UuidGenerator;

    public const MENU_CACHE_KEY = 'catalog.menu';

    protected static function booted(): void
    {
        $flushMenu = fn () => CatalogMenu::flush();

        static::saved($flushMenu);
        static::deleted($flushMenu);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Category, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /**
     * @return HasMany<Category, $this>
     */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('sort_order')->orderBy('name');
    }

    /**
     * @return HasMany<Product, $this>
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    /**
     * @param  Builder<Category>  $query
     * @return Builder<Category>
     */
    public function scopeRoots(Builder $query): Builder
    {
        return $query->whereNull('parent_id');
    }

    /**
     * Active categories whose parent (if any) is active too.
     *
     * @param  Builder<Category>  $query
     * @return Builder<Category>
     */
    public function scopeVisible(Builder $query): Builder
    {
        return $query->where('is_active', true)
            ->where(fn (Builder $query) => $query
                ->whereNull('parent_id')
                ->orWhereHas('parent', fn (Builder $parent) => $parent->where('is_active', true)));
    }

    /**
     * @param  Builder<Category>  $query
     * @return Builder<Category>
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    /**
     * Compact label for the navigation row (falls back to the full name).
     */
    public function menuName(): string
    {
        return $this->short_name ?: $this->name;
    }

    public function isRoot(): bool
    {
        return $this->parent_id === null;
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('image')->singleFile();
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')->fit(Fit::Crop, 160, 160)->format('webp')
            ->nonQueued();
        $this->addMediaConversion('card')->fit(Fit::Crop, 600, 400)->format('webp')
            ->nonQueued();
    }
}
