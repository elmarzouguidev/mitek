<?php

namespace App\Http\Requests\Admin;

use App\Models\Core\Service\Service;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ServiceFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        $service = $this->route('service');

        return $service instanceof Service
            ? $this->user()->can('update', $service)
            : $this->user()->can('create', Service::class);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'icon' => ['required', Rule::in(Service::ICONS)],
            'summary' => ['required', 'string', 'max:400'],
            'bullets' => ['required', 'array', 'min:1', 'max:20'],
            'bullets.*' => ['required', 'string', 'max:255'],
            'content' => ['nullable', 'string', 'max:20000'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:320'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:100000'],
            'is_active' => ['boolean'],
        ];
    }
}
