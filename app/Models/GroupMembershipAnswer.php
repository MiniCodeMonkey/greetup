<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GroupMembershipAnswer extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'question_id',
        'user_id',
        'answer',
    ];

    /**
     * @return BelongsTo<GroupMembershipQuestion, $this>
     */
    public function question(): BelongsTo
    {
        return $this->belongsTo(GroupMembershipQuestion::class, 'question_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
