<?php

namespace App\Http\Requests\Admin;

use App\Models\Core\Product\Product;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class ProductImageRequest extends FormRequest
{
    public const MAX_IMAGES = 10;

    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('product'));
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'images' => ['required', 'array', 'min:1', 'max:'.self::MAX_IMAGES],
            'images.*' => ['file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ];
    }

    /**
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                /** @var Product $product */
                $product = $this->route('product');
                $existing = $product->getMedia(Product::GALLERY)->count();

                if ($existing + count((array) $this->file('images')) > self::MAX_IMAGES) {
                    $validator->errors()->add('images', __('validation.custom.too_many_images', ['max' => self::MAX_IMAGES]));
                }
            },
        ];
    }
}
