<?php

namespace App\Http\Requests\Groups;

use App\Enums\GroupVisibility;
use App\Models\Group;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateGroupRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', Group::class);
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
            'description' => ['nullable', 'string', 'max:10000'],
            'location' => ['nullable', 'string', 'max:255'],
            'cover_photo' => ['nullable', 'image', 'mimes:jpeg,png,webp', 'max:10240'],
            'topics' => ['nullable', 'array'],
            'topics.*' => ['string', 'max:50'],
            'visibility' => ['required', Rule::enum(GroupVisibility::class)],
            'requires_approval' => ['boolean'],
            'max_members' => ['nullable', 'integer', 'min:2', 'max:100000'],
            'welcome_message' => ['nullable', 'string', 'max:5000'],
            'membership_questions' => ['nullable', 'array', 'max:10'],
            'membership_questions.*.question' => ['required_with:membership_questions', 'string', 'max:500'],
            'membership_questions.*.is_required' => ['boolean'],
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
            'name.required' => 'A group name is required.',
            'name.max' => 'Group name must not exceed 255 characters.',
            'description.max' => 'Description must not exceed 10,000 characters.',
            'location.max' => 'Location must not exceed 255 characters.',
            'cover_photo.image' => 'The cover photo must be an image.',
            'cover_photo.mimes' => 'The cover photo must be a JPEG, PNG, or WebP file.',
            'cover_photo.max' => 'The cover photo must not be larger than 10MB.',
            'visibility.required' => 'Please select a visibility option.',
            'max_members.min' => 'Maximum members must be at least 2.',
            'membership_questions.max' => 'You can add up to 10 membership questions.',
            'membership_questions.*.question.required_with' => 'Each membership question must have text.',
            'membership_questions.*.question.max' => 'Each question must not exceed 500 characters.',
        ];
    }
}
