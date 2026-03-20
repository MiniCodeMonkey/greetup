<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Spatie\Tags\Tag;

class UpdateInterestRequest extends FormRequest
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
            'name' => [
                'required',
                'string',
                'max:255',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    $exists = Tag::query()
                        ->where('type', 'interest')
                        ->where('name->en', $value)
                        ->where('id', '!=', $this->route('interest')->id)
                        ->exists();

                    if ($exists) {
                        $fail('An interest with this name already exists.');
                    }
                },
            ],
        ];
    }
}
