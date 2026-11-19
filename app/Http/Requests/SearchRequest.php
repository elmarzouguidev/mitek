<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SearchRequest extends FormRequest
{
    public const MAX_LENGTH = 100;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [];
    }

    public function term(): string
    {
        $term = $this->query('q');

        return is_string($term) ? mb_substr(trim($term), 0, self::MAX_LENGTH) : '';
    }
}
