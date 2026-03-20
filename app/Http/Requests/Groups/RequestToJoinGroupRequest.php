<?php

namespace App\Http\Requests\Groups;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class RequestToJoinGroupRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Gate::allows('join', $this->route('group'));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $group = $this->route('group');
        $rules = [];

        $questions = $group->membershipQuestions()->orderBy('sort_order')->get();

        foreach ($questions as $question) {
            $key = "answers.{$question->id}";
            $questionRules = ['string', 'max:5000'];

            if ($question->is_required) {
                array_unshift($questionRules, 'required');
            } else {
                array_unshift($questionRules, 'nullable');
            }

            $rules[$key] = $questionRules;
        }

        return $rules;
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        $messages = [];
        $group = $this->route('group');
        $questions = $group->membershipQuestions()->orderBy('sort_order')->get();

        foreach ($questions as $question) {
            $messages["answers.{$question->id}.required"] = 'This question requires an answer.';
            $messages["answers.{$question->id}.max"] = 'Your answer must not exceed 5,000 characters.';
        }

        return $messages;
    }
}
