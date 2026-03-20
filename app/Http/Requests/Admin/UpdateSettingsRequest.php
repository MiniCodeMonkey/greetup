<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<mixed>>
     */
    public function rules(): array
    {
        return [
            'site_name' => ['required', 'string', 'max:255'],
            'site_description' => ['nullable', 'string', 'max:500'],
            'registration_enabled' => ['nullable', 'boolean'],
            'require_email_verification' => ['nullable', 'boolean'],
            'max_groups_per_user' => ['nullable', 'integer', 'min:1'],
            'default_timezone' => ['required', 'string', 'timezone:all'],
            'default_locale' => ['required', 'string', 'max:10'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'site_name.required' => 'The site name is required.',
            'default_timezone.timezone' => 'Please select a valid IANA timezone.',
            'max_groups_per_user.min' => 'The maximum groups per user must be at least 1.',
        ];
    }
}
