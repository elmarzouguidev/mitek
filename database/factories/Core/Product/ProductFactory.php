<?php

namespace Database\Factories\Core\Product;

use App\Models\Core\Product\Brand;
use App\Models\Core\Product\Category;
use App\Models\Core\Product\Product;
use App\Models\Core\Product\ProductPriceTier;
use App\Support\Money;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'category_id' => Category::factory(),
            'brand_id' => null,
            'reference' => strtoupper(fake()->unique()->bothify('REF-####-??')),
            'name' => fake()->unique()->words(3, true),
            'excerpt' => fake()->sentence(),
            'description' => fake()->paragraphs(2, true),
            'price_ht' => Money::fromDecimal(fake()->numberBetween(20, 2000)),
            'vat_rate' => null,
            'stock_quantity' => fake()->numberBetween(10, 100),
            'low_stock_threshold' => 5,
            'weight_grams' => fake()->numberBetween(100, 5000),
            'is_active' => true,
            'is_featured' => false,
            'is_best_seller' => false,
        ];
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }

    public function featured(): static
    {
        return $this->state(['is_featured' => true]);
    }

    public function bestSeller(): static
    {
        return $this->state(['is_best_seller' => true]);
    }

    /**
     * A promotion: the previous price HT in dirhams (the current price is the promotional one) and an optional end.
     */
    public function onPromotion(string|int|float $compareAt, \DateTimeInterface|string|null $endsAt = null): static
    {
        return $this->state(['compare_at_price_ht' => Money::fromDecimal($compareAt), 'promo_ends_at' => $endsAt]);
    }

    public function outOfStock(): static
    {
        return $this->state(['stock_quantity' => 0]);
    }

    public function withBrand(?Brand $brand = null): static
    {
        return $this->state(['brand_id' => $brand?->id ?? Brand::factory()]);
    }

    public function priced(string|int|float $amount): static
    {
        return $this->state(['price_ht' => Money::fromDecimal($amount)]);
    }

    /**
     * Attach quantity tiers, as [minimum quantity => unit price HT in dirhams].
     *
     * @param  array<int, string|int|float>  $tiers
     */
    public function withTiers(array $tiers): static
    {
        return $this->afterCreating(function (Product $product) use ($tiers): void {
            foreach ($tiers as $minQuantity => $price) {
                ProductPriceTier::create([
                    'product_id' => $product->id,
                    'min_quantity' => $minQuantity,
                    'price_ht' => Money::fromDecimal($price),
                ]);
            }
        });
    }
}
