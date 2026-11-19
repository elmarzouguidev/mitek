<?php

namespace App\Http\Requests;

use App\Enums\ProductSort;
use App\Services\Core\Product\CatalogFilters;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Reads the listing filters from the query string. Public GET pages must never redirect on bad
 * input (crawlers and shared links), so unusable values are ignored instead of rejected.
 */
class CatalogFilterRequest extends FormRequest
{
    private const MAX_PRICE = 10_000_000;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [];
    }

    public function filters(): CatalogFilters
    {
        return new CatalogFilters(
            subcategorySlug: is_string($this->query('sous_categorie')) && $this->query('sous_categorie') !== '' ? $this->query('sous_categorie') : null,
            brandSlugs: collect((array) $this->query('marques', []))
                ->filter(fn (mixed $slug): bool => is_string($slug) && $slug !== '')
                ->take(30)
                ->values()
                ->all(),
            minPriceDh: $this->price('prix_min'),
            maxPriceDh: $this->price('prix_max'),
            inStockOnly: $this->boolean('en_stock'),
            sort: ProductSort::tryFrom((string) $this->query('tri')) ?? ProductSort::Featured,
            printerNames: collect((array) $this->query('imprimantes', []))
                ->filter(fn (mixed $name): bool => is_string($name) && $name !== '' && mb_strlen($name) <= 150)
                ->take(30)
                ->values()
                ->all(),
        );
    }

    private function price(string $key): ?int
    {
        $value = filter_var($this->query($key), FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => self::MAX_PRICE]]);

        return $value === false ? null : $value;
    }
}
