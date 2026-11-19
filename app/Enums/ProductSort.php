<?php

namespace App\Enums;

use App\Models\Core\Product\Product;
use Illuminate\Database\Eloquent\Builder;

enum ProductSort: string
{
    case Featured = 'pertinence';
    case PriceAscending = 'prix_croissant';
    case PriceDescending = 'prix_decroissant';
    case Name = 'nom';
    case Newest = 'nouveautes';

    public function label(): string
    {
        return __('catalog.sort.'.$this->value);
    }

    /**
     * @param  Builder<Product>  $query
     * @return Builder<Product>
     */
    public function apply(Builder $query): Builder
    {
        return match ($this) {
            self::Featured => $query->orderByDesc('products.is_featured')->orderByDesc('products.created_at')->orderBy('products.id'),
            self::PriceAscending => $query->orderBy('products.price_ht')->orderBy('products.id'),
            self::PriceDescending => $query->orderByDesc('products.price_ht')->orderBy('products.id'),
            self::Name => $query->orderBy('products.name')->orderBy('products.id'),
            self::Newest => $query->orderByDesc('products.created_at')->orderByDesc('products.id'),
        };
    }
}
