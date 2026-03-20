<?php

namespace App\Http\Requests\Events;

use App\Enums\EventType;
use App\Models\Event;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateEventRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', [Event::class, $this->route('group')]);
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
            'starts_at' => ['required', 'date', 'after:now'],
            'ends_at' => ['nullable', 'date', 'after:starts_at'],
            'event_type' => ['required', Rule::enum(EventType::class)],
            'venue_name' => ['nullable', 'required_if:event_type,in_person,hybrid', 'string', 'max:255'],
            'venue_address' => ['nullable', 'required_if:event_type,in_person,hybrid', 'string', 'max:500'],
            'online_link' => ['nullable', 'required_if:event_type,online,hybrid', 'url', 'max:2000'],
            'cover_photo' => ['nullable', 'image', 'mimes:jpeg,png,webp', 'max:5120'],
            'rsvp_limit' => ['nullable', 'integer', 'min:1', 'max:100000'],
            'guest_limit' => ['nullable', 'integer', 'min:0', 'max:10'],
            'rsvp_opens_at' => ['nullable', 'date'],
            'rsvp_closes_at' => ['nullable', 'date', 'after:rsvp_opens_at'],
            'is_chat_enabled' => ['boolean'],
            'is_comments_enabled' => ['boolean'],
            'timezone' => ['nullable', 'string', 'timezone:all'],
            'status' => ['nullable', Rule::in(['draft', 'published'])],
            'is_recurring' => ['boolean'],
            'recurrence_pattern' => ['nullable', 'required_if:is_recurring,1', Rule::in(['weekly', 'biweekly', 'monthly', 'custom'])],
            'custom_rrule' => ['nullable', 'required_if:recurrence_pattern,custom', 'string', 'max:500'],
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
            'name.required' => 'An event name is required.',
            'name.max' => 'Event name must not exceed 255 characters.',
            'description.max' => 'Description must not exceed 10,000 characters.',
            'starts_at.required' => 'A start date and time is required.',
            'starts_at.after' => 'The start date must be in the future.',
            'ends_at.after' => 'The end date must be after the start date.',
            'event_type.required' => 'Please select an event type.',
            'venue_name.required_if' => 'A venue name is required for in-person and hybrid events.',
            'venue_address.required_if' => 'A venue address is required for in-person and hybrid events.',
            'online_link.required_if' => 'An online link is required for online and hybrid events.',
            'online_link.url' => 'The online link must be a valid URL.',
            'cover_photo.image' => 'The cover photo must be an image.',
            'cover_photo.mimes' => 'The cover photo must be a JPEG, PNG, or WebP file.',
            'cover_photo.max' => 'The cover photo must not be larger than 5MB.',
            'rsvp_limit.min' => 'RSVP limit must be at least 1.',
            'rsvp_closes_at.after' => 'RSVP close date must be after the RSVP open date.',
            'recurrence_pattern.required_if' => 'Please select a recurrence pattern when making an event recurring.',
            'custom_rrule.required_if' => 'A custom RRULE string is required when using a custom recurrence pattern.',
        ];
    }
}
