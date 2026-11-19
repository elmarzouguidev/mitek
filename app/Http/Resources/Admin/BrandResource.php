<?php

namespace App\Http\Resources\Admin;

use App\Models\Core\Product\Brand;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Brand
 */
class BrandResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'is_active' => $this->is_active,
            'products_count' => $this->products_count,
            'logo' => $this->getFirstMediaUrl('logo', 'thumb') ?: null,
        ];
    }
}
