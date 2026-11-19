<?php

namespace App\Http\Requests\Admin;

use App\Models\Core\Product\Product;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        $product = $this->route('product');

        return $product instanceof Product
            ? $this->user()->can('update', $product)
            : $this->user()->can('create', Product::class);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'category_id' => ['required', 'integer', Rule::exists('categories', 'id')],
            'brand_id' => ['nullable', 'integer', Rule::exists('brands', 'id')],
            'reference' => ['nullable', 'string', 'max:100', Rule::unique('products', 'reference')->ignore($this->route('product'))],
            'name' => ['required', 'string', 'max:255'],
            'excerpt' => ['nullable', 'string', 'max:500'],
            'description' => ['nullable', 'string', 'max:20000'],
            'price_ht' => ['required', 'numeric', 'min:0', 'max:99999999'],
            'compare_at_price_ht' => ['nullable', 'numeric', 'min:0', 'max:99999999', 'gt:price_ht'],
            'promo_ends_at' => ['nullable', 'date'],
            'vat_rate' => ['nullable', 'integer', 'between:0,100'],
            'stock_quantity' => ['required', 'integer', 'min:0', 'max:10000000'],
            'low_stock_threshold' => ['required', 'integer', 'min:0', 'max:10000000'],
            'weight_grams' => ['required', 'integer', 'min:0', 'max:1000000000'],
            'is_active' => ['boolean'],
            'is_featured' => ['boolean'],
            'is_best_seller' => ['boolean'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:320'],
            'tiers' => ['nullable', 'array', 'max:20'],
            'tiers.*.min_quantity' => ['required', 'integer', 'min:2', 'max:1000000', 'distinct'],
            'tiers.*.price_ht' => ['required', 'numeric', 'min:0', 'max:99999999'],
            'specifications' => ['nullable', 'array', 'max:50'],
            'specifications.*.name' => ['required', 'string', 'max:100'],
            'specifications.*.value' => ['required', 'string', 'max:255'],
            'compatible_models' => ['nullable', 'array', 'max:100'],
            'compatible_models.*' => ['string', 'max:150'],
        ];
    }

    /**
     * A quantity tier is a discount: it can never cost more than the base price.
     *
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $basePrice = $this->input('price_ht');

                if (! is_numeric($basePrice)) {
                    return;
                }

                foreach ((array) $this->input('tiers', []) as $index => $tier) {
                    if (is_numeric($tier['price_ht'] ?? null) && (float) $tier['price_ht'] > (float) $basePrice) {
                        $validator->errors()->add("tiers.{$index}.price_ht", __('validation.custom.tier_price'));
                    }
                }
            },
        ];
    }
}
