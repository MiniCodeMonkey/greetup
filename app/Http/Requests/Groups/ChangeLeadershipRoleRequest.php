<?php

namespace App\Http\Requests\Groups;

use App\Enums\GroupRole;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class ChangeLeadershipRoleRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Gate::allows('manageLeadership', $this->route('group'));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'role' => [
                'required',
                'string',
                Rule::in([
                    GroupRole::Member->value,
                    GroupRole::EventOrganizer->value,
                    GroupRole::AssistantOrganizer->value,
                    GroupRole::CoOrganizer->value,
                ]),
            ],
        ];
    }
}
