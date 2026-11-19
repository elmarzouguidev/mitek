<?php

namespace App\Http\Requests\Admin;

use App\Settings\ClaimsSettings;
use Illuminate\Foundation\Http\FormRequest;

class UpdateClaimsSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isAdmin();
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return collect(array_keys(ClaimsSettings::descriptions()))
            ->mapWithKeys(fn (string $claim): array => [$claim => ['required', 'boolean']])
            ->all();
    }
}
