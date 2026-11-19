<?php

namespace App\Models\Core\Product;

use Database\Factories\Core\Product\CompatibleModelFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * A printer (or other device) model a consumable is compatible with, e.g. "HP Color LaserJet Pro M476dn".
 */
class CompatibleModel extends Model
{
    /** @use HasFactory<CompatibleModelFactory> */
    use HasFactory;

    /**
     * @return BelongsToMany<Product, $this>
     */
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class);
    }
}
