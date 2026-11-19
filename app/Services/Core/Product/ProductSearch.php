<?php

namespace App\Services\Core\Product;

use App\Models\Core\Product\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

/**
 * Searches visible products by name, reference, brand name and compatible model.
 * Every word of the query must match at least one of those fields.
 */
class ProductSearch
{
    public const MAX_TERMS = 5;

    /**
     * @return Builder<Product>
     */
    public function search(string $term): Builder
    {
        $words = $this->words($term);

        $query = Product::query()->visible()->with(['media', 'brand', 'priceTiers', 'specifications']);

        foreach ($words as $word) {
            $like = '%'.$this->escapeLike($word).'%';

            $query->where(fn (Builder $match) => $match
                ->whereRaw("products.name LIKE ? ESCAPE '|'", [$like])
                ->orWhereRaw("products.reference LIKE ? ESCAPE '|'", [$like])
                ->orWhereHas('brand', fn (Builder $brand) => $brand->whereRaw("name LIKE ? ESCAPE '|'", [$like]))
                ->orWhereHas('compatibleModels', fn (Builder $model) => $model->whereRaw("compatible_models.name LIKE ? ESCAPE '|'", [$like])));
        }

        return $query
            ->orderByRaw('CASE WHEN products.reference = ? THEN 0 WHEN products.name LIKE ? ESCAPE \'|\' THEN 1 ELSE 2 END', [
                $term,
                $this->escapeLike($words[0] ?? '').'%',
            ])
            ->orderBy('products.name')
            ->orderBy('products.id');
    }

    /**
     * @return array<int, string>
     */
    private function words(string $term): array
    {
        return collect(preg_split('/\s+/u', Str::squish($term), -1, PREG_SPLIT_NO_EMPTY))
            ->take(self::MAX_TERMS)
            ->values()
            ->all();
    }

    private function escapeLike(string $value): string
    {
        return str_replace(['|', '%', '_'], ['||', '|%', '|_'], $value);
    }
}
