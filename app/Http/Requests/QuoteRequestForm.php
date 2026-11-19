<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class QuoteRequestForm extends FormRequest
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
            'contact_name' => ['required', 'string', 'max:150'],
            'contact_email' => ['required', 'email', 'max:255'],
            'contact_phone' => ['required', 'string', 'phone:MA'],
            'company_name' => ['nullable', 'string', 'max:150'],
            'ice' => ['nullable', 'digits:15'],
            'message' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
