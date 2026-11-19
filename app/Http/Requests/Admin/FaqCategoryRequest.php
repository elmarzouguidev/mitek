<?php

namespace App\Http\Requests\Admin;

use App\Models\Core\Content\FaqCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FaqCategoryRequest extends FormRequest
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
        $category = $this->route('category');

        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:100', 'regex:/^[a-z0-9-]+$/', Rule::unique('faq_categories', 'slug')->ignore($category instanceof FaqCategory ? $category : null)],
            'sort_order' => ['required', 'integer', 'min:0', 'max:100000'],
        ];
    }
}
