<?php

namespace App\Http\Requests;

use App\Services\Core\Cart\CartService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DeliveryEstimateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'ville' => ['required', 'string', 'max:100'],
            'produit' => ['nullable', 'string', Rule::exists('products', 'slug')->where('is_active', true)->whereNull('deleted_at')],
            'quantite' => ['nullable', 'integer', 'min:1', 'max:'.CartService::MAX_QUANTITY_PER_LINE],
            'panier' => ['nullable', 'boolean'],
        ];
    }
}
