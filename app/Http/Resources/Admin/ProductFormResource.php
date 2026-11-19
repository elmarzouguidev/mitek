<?php

namespace App\Http\Resources\Admin;

use App\Models\Core\Product\CompatibleModel;
use App\Models\Core\Product\Product;
use App\Models\Core\Product\ProductPriceTier;
use App\Models\Core\Product\ProductSpecification;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * @mixin Product
 */
class ProductFormResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'category_id' => $this->category_id,
            'brand_id' => $this->brand_id,
            'reference' => $this->reference,
            'name' => $this->name,
            'slug' => $this->slug,
            'excerpt' => $this->excerpt,
            'description' => $this->description,
            'price_ht' => $this->price_ht->toDecimal(),
            'vat_rate' => $this->vat_rate,
            'stock_quantity' => $this->stock_quantity,
            'low_stock_threshold' => $this->low_stock_threshold,
            'weight_grams' => $this->weight_grams,
            'is_active' => $this->is_active,
            'is_featured' => $this->is_featured,
            'meta_title' => $this->meta_title,
            'meta_description' => $this->meta_description,
            'tiers' => $this->priceTiers->map(fn (ProductPriceTier $tier): array => [
                'min_quantity' => $tier->min_quantity,
                'price_ht' => $tier->price_ht->toDecimal(),
            ])->values(),
            'specifications' => $this->specifications->map(fn (ProductSpecification $specification): array => [
                'name' => $specification->name,
                'value' => $specification->value,
            ])->values(),
            'compatible_models' => $this->compatibleModels->map(fn (CompatibleModel $model): string => $model->name)->values(),
            'images' => $this->getMedia(Product::GALLERY)->map(fn (Media $media): array => [
                'id' => $media->id,
                'name' => $media->file_name,
                'url' => $media->getUrl('card'),
            ])->values(),
        ];
    }
}
