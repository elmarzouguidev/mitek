<?php

namespace App\Http\Requests;

use App\Enums\Intervention;
use App\Enums\Urgency;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ServiceRequestForm extends FormRequest
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
            'service' => ['required', 'string', Rule::exists('services', 'slug')->where('is_active', true)],
            'contact_name' => ['required', 'string', 'max:150'],
            'company_name' => ['nullable', 'string', 'max:150'],
            'phone' => ['required', 'string', 'phone:MA'],
            'email' => ['required', 'email', 'max:255'],
            'city' => ['required', 'string', 'max:100'],
            'intervention' => ['required', Rule::enum(Intervention::class)],
            'urgency' => ['required', Rule::enum(Urgency::class)],
            'message' => ['required', 'string', 'min:10', 'max:3000'],
        ];
    }
}
