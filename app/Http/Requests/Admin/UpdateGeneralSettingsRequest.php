<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateGeneralSettingsRequest extends FormRequest
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
            'site_name' => ['required', 'string', 'max:100'],
            'legal_name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'whatsapp' => ['nullable', 'string', 'regex:/^\d{8,15}$/'],
            'address' => ['required', 'string', 'max:255'],
            'maps_embed_url' => ['nullable', 'url:https', 'max:1000'],
            'vat_rate' => ['required', 'integer', 'between:0,100'],
        ];
    }
}
