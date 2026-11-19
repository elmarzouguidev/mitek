<?php

namespace App\Http\Requests;

use App\Services\Core\Cart\CartService;
use Illuminate\Foundation\Http\FormRequest;

class UpdateCartRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Zero removes the line.
     *
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'quantite' => ['required', 'integer', 'min:0', 'max:'.CartService::MAX_QUANTITY_PER_LINE],
        ];
    }
}
