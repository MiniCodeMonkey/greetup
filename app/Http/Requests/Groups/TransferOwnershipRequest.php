<?php

namespace App\Http\Requests\Groups;

use App\Enums\GroupRole;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Validator;

class TransferOwnershipRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Gate::allows('transferOwnership', $this->route('group'));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'new_owner_id' => ['required', 'integer', 'exists:users,id'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                if (! Hash::check($this->input('password'), $this->user()->password)) {
                    $validator->errors()->add('password', 'The password is incorrect.');
                }

                $group = $this->route('group');
                $newOwnerId = (int) $this->input('new_owner_id');

                $isCoOrganizer = $group->members()
                    ->where('user_id', $newOwnerId)
                    ->where('role', GroupRole::CoOrganizer->value)
                    ->where('is_banned', false)
                    ->exists();

                if (! $isCoOrganizer) {
                    $validator->errors()->add('new_owner_id', 'The selected user must be a co-organizer of this group.');
                }
            },
        ];
    }
}
