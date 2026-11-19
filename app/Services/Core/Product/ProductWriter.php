<?php

namespace App\Services\Core\Product;

use App\Http\Requests\Admin\ProductRequest;
use App\Models\Core\Product\CompatibleModel;
use App\Models\Core\Product\Product;
use App\Support\Money;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Persists a product together with its price tiers, specifications and compatible models.
 * Prices arrive in dirhams (as typed in the back office) and are stored as integer centimes.
 */
class ProductWriter
{
    public function __construct(private readonly ProductReferenceGenerator $references) {}

    /**
     * @param  array<string, mixed>  $data  validated {@see ProductRequest} input
     */
    public function save(Product $product, array $data): Product
    {
        return DB::transaction(function () use ($product, $data): Product {
            $product->fill([
                'category_id' => $data['category_id'],
                'brand_id' => $data['brand_id'] ?? null,
                'reference' => filled($data['reference'] ?? null) ? $data['reference'] : ($product->reference ?: $this->references->generate()),
                'name' => $data['name'],
                'excerpt' => $data['excerpt'] ?? null,
                'description' => $data['description'] ?? null,
                'price_ht' => Money::fromDecimal($data['price_ht']),
                'vat_rate' => $data['vat_rate'] ?? null,
                'stock_quantity' => $data['stock_quantity'],
                'low_stock_threshold' => $data['low_stock_threshold'],
                'weight_grams' => $data['weight_grams'],
                'is_active' => (bool) ($data['is_active'] ?? false),
                'is_featured' => (bool) ($data['is_featured'] ?? false),
                'meta_title' => $data['meta_title'] ?? null,
                'meta_description' => $data['meta_description'] ?? null,
            ])->save();

            $this->syncTiers($product, $data['tiers'] ?? []);
            $this->syncSpecifications($product, $data['specifications'] ?? []);
            $this->syncCompatibleModels($product, $data['compatible_models'] ?? []);

            return $product;
        });
    }

    /**
     * @param  array<int, array{min_quantity: int|string, price_ht: string|int|float}>  $tiers
     */
    private function syncTiers(Product $product, array $tiers): void
    {
        $product->priceTiers()->delete();

        foreach ($tiers as $tier) {
            $product->priceTiers()->create([
                'min_quantity' => (int) $tier['min_quantity'],
                'price_ht' => Money::fromDecimal($tier['price_ht']),
            ]);
        }
    }

    /**
     * @param  array<int, array{name: string, value: string}>  $specifications
     */
    private function syncSpecifications(Product $product, array $specifications): void
    {
        $product->specifications()->delete();

        foreach (array_values($specifications) as $position => $specification) {
            $product->specifications()->create([
                'name' => $specification['name'],
                'value' => $specification['value'],
                'position' => $position,
            ]);
        }
    }

    /**
     * @param  array<int, string>  $names
     */
    private function syncCompatibleModels(Product $product, array $names): void
    {
        $ids = collect($names)
            ->map(fn (string $name): string => Str::squish($name))
            ->filter()
            ->unique(fn (string $name): string => mb_strtolower($name))
            ->map(fn (string $name): int => CompatibleModel::firstOrCreate(['name' => $name])->id)
            ->all();

        $product->compatibleModels()->sync($ids);
    }
}
