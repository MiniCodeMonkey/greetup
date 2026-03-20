<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class MergeInterestRequest extends FormRequest
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
            'target_id' => [
                'required',
                'integer',
                'exists:tags,id',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if ((int) $value === $this->route('interest')->id) {
                        $fail('Cannot merge an interest into itself.');
                    }
                },
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'target_id.required' => 'Please select a target interest to merge into.',
            'target_id.exists' => 'The selected target interest does not exist.',
        ];
    }
}
