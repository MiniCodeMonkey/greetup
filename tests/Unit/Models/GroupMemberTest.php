<?php

use App\Enums\GroupRole;
use App\Enums\JoinRequestStatus;
use App\Models\Group;
use App\Models\GroupJoinRequest;
use App\Models\GroupMember;
use App\Models\GroupMembershipAnswer;
use App\Models\GroupMembershipQuestion;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);
});

// --- GroupMember pivot ---

it('casts role to GroupRole enum on pivot', function (): void {
    $group = Group::factory()->create();
    $user = User::factory()->create();

    $group->members()->attach($user->id, [
        'role' => 'event_organizer',
        'joined_at' => now(),
    ]);

    $member = $group->members()->first();

    expect($member->pivot)->toBeInstanceOf(GroupMember::class)
        ->and($member->pivot->role)->toBe(GroupRole::EventOrganizer)
        ->and($member->pivot->role)->toBeInstanceOf(GroupRole::class);
});

it('casts is_banned to boolean on pivot', function (): void {
    $group = Group::factory()->create();
    $user = User::factory()->create();

    $group->members()->attach($user->id, [
        'role' => 'member',
        'joined_at' => now(),
        'is_banned' => true,
        'banned_at' => now(),
        'banned_reason' => 'Spamming',
    ]);

    $member = $group->members()->first();

    expect($member->pivot->is_banned)->toBeBool()
        ->and($member->pivot->is_banned)->toBeTrue()
        ->and($member->pivot->banned_at)->not->toBeNull()
        ->and($member->pivot->banned_reason)->toBe('Spamming');
});

it('casts joined_at to datetime on pivot', function (): void {
    $group = Group::factory()->create();
    $user = User::factory()->create();
    $now = now();

    $group->members()->attach($user->id, [
        'role' => 'member',
        'joined_at' => $now,
    ]);

    $member = $group->members()->first();

    expect($member->pivot->joined_at)->toBeInstanceOf(Carbon::class);
});

it('defaults is_banned to false on pivot', function (): void {
    $group = Group::factory()->create();
    $user = User::factory()->create();

    $group->members()->attach($user->id, [
        'role' => 'member',
        'joined_at' => now(),
    ]);

    $member = $group->members()->first();

    expect($member->pivot->is_banned)->toBeFalse();
});

it('has group belongsTo relationship on pivot', function (): void {
    $group = Group::factory()->create();
    $user = User::factory()->create();

    $group->members()->attach($user->id, [
        'role' => 'member',
        'joined_at' => now(),
    ]);

    $pivot = GroupMember::first();

    expect($pivot->group())->toBeInstanceOf(BelongsTo::class)
        ->and($pivot->group->id)->toBe($group->id);
});

it('has user belongsTo relationship on pivot', function (): void {
    $group = Group::factory()->create();
    $user = User::factory()->create();

    $group->members()->attach($user->id, [
        'role' => 'member',
        'joined_at' => now(),
    ]);

    $pivot = GroupMember::first();

    expect($pivot->user())->toBeInstanceOf(BelongsTo::class)
        ->and($pivot->user->id)->toBe($user->id);
});

it('enforces unique group_id and user_id constraint', function (): void {
    $group = Group::factory()->create();
    $user = User::factory()->create();

    $group->members()->attach($user->id, ['role' => 'member', 'joined_at' => now()]);

    $group->members()->attach($user->id, ['role' => 'organizer', 'joined_at' => now()]);
})->throws(QueryException::class);

// --- GroupMembershipQuestion ---

it('can create a membership question', function (): void {
    $group = Group::factory()->create();

    $question = GroupMembershipQuestion::create([
        'group_id' => $group->id,
        'question' => 'Why do you want to join?',
        'is_required' => true,
        'sort_order' => 1,
    ]);

    expect($question->exists)->toBeTrue()
        ->and($question->question)->toBe('Why do you want to join?')
        ->and($question->is_required)->toBeTrue()
        ->and($question->sort_order)->toBe(1);
});

it('has group relationship on membership question', function (): void {
    $group = Group::factory()->create();
    $question = GroupMembershipQuestion::create([
        'group_id' => $group->id,
        'question' => 'Tell us about yourself',
    ]);

    expect($question->group())->toBeInstanceOf(BelongsTo::class)
        ->and($question->group->id)->toBe($group->id);
});

it('has answers relationship on membership question', function (): void {
    $group = Group::factory()->create();
    $question = GroupMembershipQuestion::create([
        'group_id' => $group->id,
        'question' => 'Tell us about yourself',
    ]);

    expect($question->answers())->toBeInstanceOf(HasMany::class);
});

it('casts is_required to boolean on membership question', function (): void {
    $group = Group::factory()->create();
    $question = GroupMembershipQuestion::create([
        'group_id' => $group->id,
        'question' => 'Test',
        'is_required' => false,
    ]);

    expect($question->is_required)->toBeBool()
        ->and($question->is_required)->toBeFalse();
});

it('casts sort_order to integer on membership question', function (): void {
    $group = Group::factory()->create();
    $question = GroupMembershipQuestion::create([
        'group_id' => $group->id,
        'question' => 'Test',
        'sort_order' => 3,
    ]);

    expect($question->sort_order)->toBeInt()
        ->and($question->sort_order)->toBe(3);
});

it('group has membershipQuestions relationship', function (): void {
    $group = Group::factory()->create();

    expect($group->membershipQuestions())->toBeInstanceOf(HasMany::class);

    GroupMembershipQuestion::create([
        'group_id' => $group->id,
        'question' => 'Question 1',
        'sort_order' => 0,
    ]);
    GroupMembershipQuestion::create([
        'group_id' => $group->id,
        'question' => 'Question 2',
        'sort_order' => 1,
    ]);

    expect($group->membershipQuestions)->toHaveCount(2);
});

// --- GroupMembershipAnswer ---

it('can create a membership answer', function (): void {
    $group = Group::factory()->create();
    $user = User::factory()->create();
    $question = GroupMembershipQuestion::create([
        'group_id' => $group->id,
        'question' => 'Why do you want to join?',
    ]);

    $answer = GroupMembershipAnswer::create([
        'question_id' => $question->id,
        'user_id' => $user->id,
        'answer' => 'I love hiking!',
    ]);

    expect($answer->exists)->toBeTrue()
        ->and($answer->answer)->toBe('I love hiking!');
});

it('has question and user relationships on membership answer', function (): void {
    $group = Group::factory()->create();
    $user = User::factory()->create();
    $question = GroupMembershipQuestion::create([
        'group_id' => $group->id,
        'question' => 'Why?',
    ]);

    $answer = GroupMembershipAnswer::create([
        'question_id' => $question->id,
        'user_id' => $user->id,
        'answer' => 'Because',
    ]);

    expect($answer->question())->toBeInstanceOf(BelongsTo::class)
        ->and($answer->question->id)->toBe($question->id)
        ->and($answer->user())->toBeInstanceOf(BelongsTo::class)
        ->and($answer->user->id)->toBe($user->id);
});

it('enforces unique question_id and user_id on answers', function (): void {
    $group = Group::factory()->create();
    $user = User::factory()->create();
    $question = GroupMembershipQuestion::create([
        'group_id' => $group->id,
        'question' => 'Why?',
    ]);

    GroupMembershipAnswer::create([
        'question_id' => $question->id,
        'user_id' => $user->id,
        'answer' => 'First answer',
    ]);

    GroupMembershipAnswer::create([
        'question_id' => $question->id,
        'user_id' => $user->id,
        'answer' => 'Duplicate answer',
    ]);
})->throws(QueryException::class);

// --- GroupJoinRequest ---

it('can create a join request with default pending status', function (): void {
    $group = Group::factory()->create();
    $user = User::factory()->create();

    $request = GroupJoinRequest::create([
        'group_id' => $group->id,
        'user_id' => $user->id,
    ]);

    expect($request->exists)->toBeTrue()
        ->and($request->status)->toBe(JoinRequestStatus::Pending);
});

it('casts status to JoinRequestStatus enum', function (): void {
    $group = Group::factory()->create();
    $user = User::factory()->create();

    $request = GroupJoinRequest::create([
        'group_id' => $group->id,
        'user_id' => $user->id,
        'status' => 'approved',
    ]);

    expect($request->status)->toBeInstanceOf(JoinRequestStatus::class)
        ->and($request->status)->toBe(JoinRequestStatus::Approved);
});

it('has group, user, and reviewer relationships on join request', function (): void {
    $group = Group::factory()->create();
    $user = User::factory()->create();
    $reviewer = User::factory()->create();

    $request = GroupJoinRequest::create([
        'group_id' => $group->id,
        'user_id' => $user->id,
        'status' => 'approved',
        'reviewed_by' => $reviewer->id,
        'reviewed_at' => now(),
    ]);

    expect($request->group())->toBeInstanceOf(BelongsTo::class)
        ->and($request->group->id)->toBe($group->id)
        ->and($request->user())->toBeInstanceOf(BelongsTo::class)
        ->and($request->user->id)->toBe($user->id)
        ->and($request->reviewer())->toBeInstanceOf(BelongsTo::class)
        ->and($request->reviewer->id)->toBe($reviewer->id);
});

it('casts reviewed_at to datetime on join request', function (): void {
    $group = Group::factory()->create();
    $user = User::factory()->create();

    $request = GroupJoinRequest::create([
        'group_id' => $group->id,
        'user_id' => $user->id,
        'reviewed_at' => now(),
    ]);

    expect($request->reviewed_at)->toBeInstanceOf(Carbon::class);
});

it('group has joinRequests relationship', function (): void {
    $group = Group::factory()->create();

    expect($group->joinRequests())->toBeInstanceOf(HasMany::class);
});

it('enforces unique group_id and user_id on join requests', function (): void {
    $group = Group::factory()->create();
    $user = User::factory()->create();

    GroupJoinRequest::create([
        'group_id' => $group->id,
        'user_id' => $user->id,
    ]);

    GroupJoinRequest::create([
        'group_id' => $group->id,
        'user_id' => $user->id,
    ]);
})->throws(QueryException::class);

it('stores denial reason on denied join request', function (): void {
    $group = Group::factory()->create();
    $user = User::factory()->create();
    $reviewer = User::factory()->create();

    $request = GroupJoinRequest::create([
        'group_id' => $group->id,
        'user_id' => $user->id,
        'status' => 'denied',
        'reviewed_by' => $reviewer->id,
        'reviewed_at' => now(),
        'denial_reason' => 'Profile incomplete',
    ]);

    expect($request->status)->toBe(JoinRequestStatus::Denied)
        ->and($request->denial_reason)->toBe('Profile incomplete');
});
