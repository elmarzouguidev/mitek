<?php

namespace App\Http\Requests\Admin;

use App\Enums\CallbackStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCallbackRequestRequest extends FormRequest
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
            'status' => ['required', Rule::enum(CallbackStatus::class)],
            'internal_notes' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
