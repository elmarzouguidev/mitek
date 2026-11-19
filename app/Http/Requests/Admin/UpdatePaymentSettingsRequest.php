<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePaymentSettingsRequest extends FormRequest
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
            'bank_name' => ['required', 'string', 'max:100'],
            'account_holder' => ['required', 'string', 'max:150'],
            'rib' => ['nullable', 'string', 'max:40'],
            'cod_limit' => ['required', 'numeric', 'min:0', 'max:1000000'],
            'cheque_payee' => ['required', 'string', 'max:150'],
            'bank_transfer_delay' => ['required', 'string', 'max:100'],
            'cash_deposit_delay' => ['required', 'string', 'max:100'],
            'cheque_delay' => ['required', 'string', 'max:100'],
        ];
    }
}
