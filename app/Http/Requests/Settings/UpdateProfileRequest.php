<?php

namespace App\Http\Requests\Settings;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProfileRequest extends FormRequest
{
    /**
     * The valid "looking for" options.
     *
     * @var list<string>
     */
    public const LOOKING_FOR_OPTIONS = [
        'practicing hobbies',
        'making friends',
        'networking',
        'professional development',
        'learning new things',
    ];

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'bio' => ['nullable', 'string', 'max:1000'],
            'location' => ['nullable', 'string', 'max:255'],
            'timezone' => ['nullable', 'string', Rule::in(timezone_identifiers_list())],
            'avatar' => ['nullable', 'image', 'mimes:jpeg,png,webp', 'max:2048'],
            'interests' => ['nullable', 'array'],
            'interests.*' => ['string', 'max:50'],
            'looking_for' => ['nullable', 'array'],
            'looking_for.*' => ['string', Rule::in(self::LOOKING_FOR_OPTIONS)],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'A name is required.',
            'name.max' => 'Name must not exceed 255 characters.',
            'bio.max' => 'Bio must not exceed 1000 characters.',
            'location.max' => 'Location must not exceed 255 characters.',
            'timezone.in' => 'Please select a valid timezone.',
            'avatar.image' => 'The avatar must be an image.',
            'avatar.mimes' => 'The avatar must be a JPEG, PNG, or WebP file.',
            'avatar.max' => 'The avatar must not be larger than 2MB.',
            'looking_for.*.in' => 'Invalid "looking for" option selected.',
        ];
    }
}
