<?php

namespace App\Http\Requests;

use App\Enums\CallbackSlot;
use App\Enums\CallbackSubject;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CallbackRequestForm extends FormRequest
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
            'name' => ['required', 'string', 'max:150'],
            'phone' => ['required', 'string', 'phone:MA'],
            'subject' => ['required', Rule::enum(CallbackSubject::class)],
            'time_slot' => ['required', Rule::enum(CallbackSlot::class)],
            'note' => ['nullable', 'string', 'max:500'],
        ];
    }
}
