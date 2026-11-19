<?php

namespace App\Models\Core\Product;

use App\Casts\MoneyCast;
use App\Policies\ProductPolicy;
use App\Services\Core\Product\CatalogMenu;
use App\Traits\HasSlug;
use App\Traits\UuidGenerator;
use Database\Factories\Core\Product\ProductFactory;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

#[UsePolicy(ProductPolicy::class)]
class Product extends Model implements HasMedia
{
    /** @use HasFactory<ProductFactory> */
    use HasFactory, HasSlug, InteractsWithMedia, SoftDeletes, UuidGenerator;

    public const GALLERY = 'gallery';

    protected static function booted(): void
    {
        $flushMenu = fn () => CatalogMenu::flush();

        static::saved($flushMenu);
        static::deleted($flushMenu);
        static::restored($flushMenu);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'price_ht' => MoneyCast::class,
            'compare_at_price_ht' => MoneyCast::class,
            'promo_ends_at' => 'datetime',
            'is_best_seller' => 'boolean',
            'vat_rate' => 'integer',
            'stock_quantity' => 'integer',
            'low_stock_threshold' => 'integer',
            'weight_grams' => 'integer',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
        ];
    }

    /**
     * Products with a running promotion: a "compare at" price above the current one, not yet expired.
     *
     * @param  Builder<Product>  $query
     * @return Builder<Product>
     */
    public function scopeOnPromotion(Builder $query): Builder
    {
        return $query->whereNotNull('products.compare_at_price_ht')
            ->whereColumn('products.compare_at_price_ht', '>', 'products.price_ht')
            ->where(fn (Builder $ends) => $ends->whereNull('products.promo_ends_at')->orWhere('products.promo_ends_at', '>', now()));
    }

    /**
     * @param  Builder<Product>  $query
     * @return Builder<Product>
     */
    public function scopeBestSellers(Builder $query): Builder
    {
        return $query->where('products.is_best_seller', true);
    }

    /**
     * @param  Builder<Product>  $query
     * @return Builder<Product>
     */
    public function scopeAddedWithin(Builder $query, int $days): Builder
    {
        return $query->where('products.created_at', '>=', now()->subDays($days));
    }

    /**
     * @return BelongsTo<Category, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * @return BelongsTo<Brand, $this>
     */
    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    /**
     * @return HasMany<ProductPriceTier, $this>
     */
    public function priceTiers(): HasMany
    {
        return $this->hasMany(ProductPriceTier::class)->orderBy('min_quantity');
    }

    /**
     * @return HasMany<ProductSpecification, $this>
     */
    public function specifications(): HasMany
    {
        return $this->hasMany(ProductSpecification::class)->orderBy('position');
    }

    /**
     * @return BelongsToMany<CompatibleModel, $this>
     */
    public function compatibleModels(): BelongsToMany
    {
        return $this->belongsToMany(CompatibleModel::class)->orderBy('name');
    }

    /**
     * Products that are active, in an active category (whose parent is active too).
     *
     * @param  Builder<Product>  $query
     * @return Builder<Product>
     */
    public function scopeVisible(Builder $query): Builder
    {
        return $query->where('products.is_active', true)
            ->whereHas('category', fn (Builder $category) => $category->visible());
    }

    /**
     * @param  Builder<Product>  $query
     * @return Builder<Product>
     */
    public function scopeInStock(Builder $query): Builder
    {
        return $query->where('products.stock_quantity', '>', 0);
    }

    /**
     * @param  Builder<Product>  $query
     * @return Builder<Product>
     */
    public function scopeLowStock(Builder $query): Builder
    {
        return $query->whereColumn('products.stock_quantity', '<=', 'products.low_stock_threshold');
    }

    public function isInStock(): bool
    {
        return $this->stock_quantity > 0;
    }

    public function isLowStock(): bool
    {
        return $this->stock_quantity <= $this->low_stock_threshold;
    }

    /**
     * Alt text for every gallery image defaults to the product name.
     */
    public function imageAlt(): string
    {
        return $this->name;
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection(self::GALLERY);
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->performOnCollections(self::GALLERY)
            ->fit(Fit::Crop, 160, 160)
            ->format('webp')
            ->nonQueued();

        $this->addMediaConversion('card')
            ->performOnCollections(self::GALLERY)
            ->fit(Fit::Max, 480, 480)
            ->format('webp')
            ->nonQueued();

        $this->addMediaConversion('large')
            ->performOnCollections(self::GALLERY)
            ->fit(Fit::Max, 1200, 1200)
            ->format('webp')
            ->nonQueued();
    }
}
