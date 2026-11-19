<?php

namespace App\Http\Requests;

use App\Services\Core\Cart\CartService;
use Illuminate\Foundation\Http\FormRequest;

class AddToCartRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'product' => ['required', 'string', 'max:255'],
            'quantite' => ['required', 'integer', 'min:1', 'max:'.CartService::MAX_QUANTITY_PER_LINE],
        ];
    }
}
