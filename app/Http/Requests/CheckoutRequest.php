<?php

namespace App\Http\Requests;

use App\Enums\CarrierMode;
use App\Enums\PaymentMethod;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CheckoutRequest extends FormRequest
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
            'contact_name' => ['required', 'string', 'max:150'],
            'contact_email' => ['required', 'email', 'max:255'],
            'contact_phone' => ['required', 'string', 'phone:MA'],
            'is_company' => ['boolean'],
            'company_name' => ['nullable', Rule::requiredIf(fn (): bool => $this->boolean('is_company')), 'string', 'max:150'],
            'ice' => ['nullable', 'digits:15'],
            'if_number' => ['nullable', 'string', 'max:30'],
            'rc_number' => ['nullable', 'string', 'max:30'],
            'shipping_city' => ['required', 'string', 'max:100'],
            'shipping_address' => ['required', 'string', 'max:500'],
            'shipping_postal_code' => ['nullable', 'string', 'max:20'],
            'carrier_mode' => ['required', Rule::enum(CarrierMode::class)],
            'own_carrier_note' => ['nullable', 'required_if:carrier_mode,'.CarrierMode::Own->value, 'string', 'max:1000'],
            'payment_method' => ['required', Rule::enum(PaymentMethod::class)],
            'customer_note' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * Company fields only count for B2B orders.
     *
     * @param  array<int, string>|int|string|null  $key
     * @return array<string, mixed>
     */
    public function validated($key = null, $default = null): mixed
    {
        $data = parent::validated();

        if (! ($data['is_company'] ?? false)) {
            $data = array_merge($data, ['company_name' => null, 'ice' => null, 'if_number' => null, 'rc_number' => null]);
        }

        return $key === null ? $data : data_get($data, $key, $default);
    }
}
