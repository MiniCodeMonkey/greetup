<?php

namespace App\Http\Requests\Reports;

use App\Enums\ReportReason;
use App\Models\Comment;
use App\Models\Discussion;
use App\Models\DiscussionReply;
use App\Models\Event;
use App\Models\EventChatMessage;
use App\Models\Group;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreReportRequest extends FormRequest
{
    /**
     * The allowed reportable types mapped to their model classes.
     *
     * @var array<string, class-string>
     */
    public static array $reportableTypes = [
        'user' => User::class,
        'group' => Group::class,
        'event' => Event::class,
        'comment' => Comment::class,
        'discussion' => Discussion::class,
        'discussion_reply' => DiscussionReply::class,
        'chat_message' => EventChatMessage::class,
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
            'reportable_type' => ['required', 'string', Rule::in(array_keys(self::$reportableTypes))],
            'reportable_id' => ['required', 'integer'],
            'reason' => ['required', 'string', Rule::in(array_column(ReportReason::cases(), 'value'))],
            'description' => ['nullable', 'string', 'max:1000'],
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
            'reportable_type.required' => 'The content type is required.',
            'reportable_type.in' => 'The selected content type is invalid.',
            'reportable_id.required' => 'The content ID is required.',
            'reason.required' => 'A reason for the report is required.',
            'reason.in' => 'The selected reason is invalid.',
            'description.max' => 'The description must not exceed 1,000 characters.',
        ];
    }

    /**
     * Resolve the reportable type alias to the full model class name.
     */
    public function reportableModelClass(): string
    {
        return self::$reportableTypes[$this->input('reportable_type')];
    }
}
