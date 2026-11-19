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
            'maps_embed_url' => ['nullable', 'url:https', 'max:1000', 'starts_with:https://www.google.com/maps/embed,https://maps.google.com/'],
            'vat_rate' => ['required', 'integer', 'between:0,100'],
            'ice' => ['nullable', 'digits:15'],
            'tp_number' => ['nullable', 'string', 'max:30'],
            'cnss_number' => ['nullable', 'string', 'max:30'],
            'legal_form' => ['required', 'string', 'max:50'],
            'share_capital' => ['nullable', 'string', 'max:50'],
            'registered_address' => ['nullable', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:100'],
            'admin_notification_email' => ['required', 'email', 'max:255'],
            'opening_hours' => ['nullable', 'string', 'max:255'],
            'whatsapp_hours' => ['nullable', 'string', 'max:255'],
            'facebook_url' => ['nullable', 'url:https', 'max:255'],
            'instagram_url' => ['nullable', 'url:https', 'max:255'],
            'linkedin_url' => ['nullable', 'url:https', 'max:255'],
            'youtube_url' => ['nullable', 'url:https', 'max:255'],
            'tiktok_url' => ['nullable', 'url:https', 'max:255'],
            'cndp_number' => ['nullable', 'string', 'max:50'],
            'warranty_text' => ['nullable', 'string', 'max:255'],
            'if_number' => ['nullable', 'string', 'max:30'],
            'rc_number' => ['nullable', 'string', 'max:30'],
        ];
    }
}
