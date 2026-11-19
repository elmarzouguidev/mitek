<?php

namespace App\Http\Resources\Admin;

use App\Models\Core\Product\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Product
 */
class ProductListResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'reference' => $this->reference,
            'name' => $this->name,
            'slug' => $this->slug,
            'category' => $this->category?->name,
            'brand' => $this->brand?->name,
            'price_ht' => $this->price_ht->format(),
            'stock_quantity' => $this->stock_quantity,
            'is_low_stock' => $this->isLowStock(),
            'is_active' => $this->is_active,
            'is_featured' => $this->is_featured,
            'thumb' => $this->getFirstMediaUrl(Product::GALLERY, 'thumb') ?: null,
        ];
    }
}
