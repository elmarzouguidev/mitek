<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($this->user())],
            'phone' => ['nullable', 'string', 'phone:MA'],
            'company_name' => ['nullable', 'string', 'max:150'],
            'ice' => ['nullable', 'digits:15'],
            'if_number' => ['nullable', 'string', 'max:30'],
            'rc_number' => ['nullable', 'string', 'max:30'],
        ];
    }
}
