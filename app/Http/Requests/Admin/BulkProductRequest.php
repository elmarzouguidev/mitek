<?php

namespace App\Http\Requests\Admin;

use App\Models\Core\Product\Product;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BulkProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', new Product);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'action' => ['required', Rule::in(['activate', 'deactivate'])],
            'ids' => ['required', 'array', 'min:1', 'max:200'],
            'ids.*' => ['integer', Rule::exists('products', 'id')],
        ];
    }
}
