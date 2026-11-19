<?php

namespace App\Models\Core\Product;

use App\Casts\MoneyCast;
use App\Traits\GetModelByKeyName;
use App\Traits\HasSlug;
use App\Traits\UuidGenerator;
use Database\Factories\Core\Product\ProductFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use GetModelByKeyName, HasSlug, UuidGenerator;

    /** @use HasFactory<ProductFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_valid' => 'boolean',
            'price_ht' => MoneyCast::class,
        ];
    }
}
