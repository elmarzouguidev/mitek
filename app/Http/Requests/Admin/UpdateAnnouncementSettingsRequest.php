<?php

namespace App\Http\Requests\Admin;

use Closure;
use Illuminate\Foundation\Http\FormRequest;

class UpdateAnnouncementSettingsRequest extends FormRequest
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
            'is_active' => ['boolean'],
            'dismissible' => ['boolean'],
            'text' => ['nullable', 'required_if_accepted:is_active', 'string', 'max:200'],
            'link_url' => ['nullable', 'string', 'max:500', $this->internalOrHttps()],
            'link_label' => ['nullable', 'required_with:link_url', 'string', 'max:60'],
            'starts_at' => ['nullable', 'date_format:Y-m-d\TH:i'],
            'ends_at' => ['nullable', 'date_format:Y-m-d\TH:i', 'after:starts_at'],
        ];
    }

    /**
     * Only https links or paths of the site itself: never javascript:, data: or protocol-relative URLs.
     */
    private function internalOrHttps(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            $isInternal = is_string($value) && str_starts_with($value, '/') && ! str_starts_with($value, '//');
            $isHttps = is_string($value) && str_starts_with($value, 'https://') && filter_var($value, FILTER_VALIDATE_URL) !== false;

            if (! $isInternal && ! $isHttps) {
                $fail(__('validation.custom.link_scheme'));
            }
        };
    }
}
