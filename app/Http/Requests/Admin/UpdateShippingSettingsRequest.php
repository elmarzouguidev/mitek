<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateShippingSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isAdmin();
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'dispatch_days' => ['required', 'integer', 'between:0,30'],
            'delivery_estimate' => ['required', 'string', 'max:150'],
            'own_carrier_fee' => ['required', 'numeric', 'min:0', 'max:100000'],
        ];
    }
}
