<?php

use App\Enums\GroupRole;
use App\Enums\JoinRequestStatus;
use App\Models\Group;
use App\Models\GroupJoinRequest;
use App\Models\GroupMembershipQuestion;
use App\Models\User;
use App\Notifications\JoinRequestApproved;
use App\Notifications\JoinRequestDenied;
use App\Notifications\JoinRequestReceived;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);
});

function createApprovalGroup(): array
{
    $organizer = User::factory()->create();
    $group = Group::factory()->requiresApproval()->create(['organizer_id' => $organizer->id]);
    $group->members()->attach($organizer->id, [
        'role' => GroupRole::Organizer->value,
        'joined_at' => now(),
    ]);

    return [$group, $organizer];
}

it('allows a verified user to request to join an approval-required group with answers', function (): void {
    Notification::fake();

    [$group, $organizer] = createApprovalGroup();

    $q1 = GroupMembershipQuestion::create([
        'group_id' => $group->id,
        'question' => 'Why do you want to join?',
        'is_required' => true,
        'sort_order' => 0,
    ]);
    $q2 = GroupMembershipQuestion::create([
        'group_id' => $group->id,
        'question' => 'How did you hear about us?',
        'is_required' => false,
        'sort_order' => 1,
    ]);

    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('groups.request-join', $group), [
            'answers' => [
                $q1->id => 'I love this community!',
                $q2->id => 'A friend told me.',
            ],
        ])
        ->assertRedirect(route('groups.show', $group))
        ->assertSessionHas('status');

    $this->assertDatabaseHas('group_join_requests', [
        'group_id' => $group->id,
        'user_id' => $user->id,
        'status' => JoinRequestStatus::Pending->value,
    ]);

    $this->assertDatabaseHas('group_membership_answers', [
        'question_id' => $q1->id,
        'user_id' => $user->id,
        'answer' => 'I love this community!',
    ]);

    $this->assertDatabaseHas('group_membership_answers', [
        'question_id' => $q2->id,
        'user_id' => $user->id,
        'answer' => 'A friend told me.',
    ]);

    Notification::assertSentTo($organizer, JoinRequestReceived::class, function ($notification) use ($group, $user) {
        return $notification->joinRequest->group_id === $group->id
            && $notification->joinRequest->user_id === $user->id;
    });
});

it('validates required membership questions', function (): void {
    Notification::fake();

    [$group] = createApprovalGroup();

    $requiredQuestion = GroupMembershipQuestion::create([
        'group_id' => $group->id,
        'question' => 'Why do you want to join?',
        'is_required' => true,
        'sort_order' => 0,
    ]);

    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('groups.request-join', $group), [
            'answers' => [
                $requiredQuestion->id => '',
            ],
        ])
        ->assertSessionHasErrors("answers.{$requiredQuestion->id}");

    $this->assertDatabaseMissing('group_join_requests', [
        'group_id' => $group->id,
        'user_id' => $user->id,
    ]);
});

it('shows Request Pending status on group page after requesting', function (): void {
    Notification::fake();

    [$group] = createApprovalGroup();

    $user = User::factory()->create();

    GroupJoinRequest::create([
        'group_id' => $group->id,
        'user_id' => $user->id,
        'status' => JoinRequestStatus::Pending,
    ]);

    $this->actingAs($user)
        ->get(route('groups.show', $group))
        ->assertOk()
        ->assertSee('Request Pending');
});

it('approves a join request and sends notification', function (): void {
    Notification::fake();

    [$group, $organizer] = createApprovalGroup();

    $user = User::factory()->create();
    $joinRequest = GroupJoinRequest::create([
        'group_id' => $group->id,
        'user_id' => $user->id,
        'status' => JoinRequestStatus::Pending,
    ]);

    $this->actingAs($organizer)
        ->post(route('groups.join-requests.approve', [$group, $joinRequest]))
        ->assertRedirect(route('groups.show', $group))
        ->assertSessionHas('status');

    $this->assertDatabaseHas('group_join_requests', [
        'id' => $joinRequest->id,
        'status' => JoinRequestStatus::Approved->value,
        'reviewed_by' => $organizer->id,
    ]);

    $this->assertDatabaseHas('group_members', [
        'group_id' => $group->id,
        'user_id' => $user->id,
        'role' => GroupRole::Member->value,
    ]);

    Notification::assertSentTo($user, JoinRequestApproved::class, function ($notification) use ($joinRequest) {
        return $notification->joinRequest->id === $joinRequest->id;
    });
});

it('denies a join request with reason and sends notification', function (): void {
    Notification::fake();

    [$group, $organizer] = createApprovalGroup();

    $user = User::factory()->create();
    $joinRequest = GroupJoinRequest::create([
        'group_id' => $group->id,
        'user_id' => $user->id,
        'status' => JoinRequestStatus::Pending,
    ]);

    $denialReason = 'Profile does not meet requirements.';

    $this->actingAs($organizer)
        ->post(route('groups.join-requests.deny', [$group, $joinRequest]), [
            'reason' => $denialReason,
        ])
        ->assertRedirect(route('groups.show', $group))
        ->assertSessionHas('status');

    $this->assertDatabaseHas('group_join_requests', [
        'id' => $joinRequest->id,
        'status' => JoinRequestStatus::Denied->value,
        'reviewed_by' => $organizer->id,
        'denial_reason' => $denialReason,
    ]);

    $this->assertDatabaseMissing('group_members', [
        'group_id' => $group->id,
        'user_id' => $user->id,
    ]);

    Notification::assertSentTo($user, JoinRequestDenied::class, function ($notification) use ($joinRequest, $denialReason) {
        return $notification->joinRequest->id === $joinRequest->id
            && $notification->joinRequest->denial_reason === $denialReason;
    });
});

it('allows re-requesting after denial by updating existing record', function (): void {
    Notification::fake();

    [$group] = createApprovalGroup();

    $user = User::factory()->create();
    $joinRequest = GroupJoinRequest::create([
        'group_id' => $group->id,
        'user_id' => $user->id,
        'status' => JoinRequestStatus::Denied,
        'reviewed_by' => $group->organizer_id,
        'reviewed_at' => now()->subDay(),
        'denial_reason' => 'Not enough info.',
    ]);

    $this->actingAs($user)
        ->post(route('groups.request-join', $group))
        ->assertRedirect(route('groups.show', $group))
        ->assertSessionHas('status');

    $joinRequest->refresh();
    expect($joinRequest->status)->toBe(JoinRequestStatus::Pending)
        ->and($joinRequest->reviewed_by)->toBeNull()
        ->and($joinRequest->reviewed_at)->toBeNull()
        ->and($joinRequest->denial_reason)->toBeNull();

    // Ensure no duplicate record was created
    expect(GroupJoinRequest::where('group_id', $group->id)->where('user_id', $user->id)->count())->toBe(1);
});

it('prevents non-leadership from approving requests', function (): void {
    [$group] = createApprovalGroup();

    $regularMember = User::factory()->create();
    $group->members()->attach($regularMember->id, [
        'role' => GroupRole::Member->value,
        'joined_at' => now(),
    ]);

    $user = User::factory()->create();
    $joinRequest = GroupJoinRequest::create([
        'group_id' => $group->id,
        'user_id' => $user->id,
        'status' => JoinRequestStatus::Pending,
    ]);

    $this->actingAs($regularMember)
        ->post(route('groups.join-requests.approve', [$group, $joinRequest]))
        ->assertForbidden();
});

it('allows assistant organizer to approve requests', function (): void {
    Notification::fake();

    [$group] = createApprovalGroup();

    $assistant = User::factory()->create();
    $group->members()->attach($assistant->id, [
        'role' => GroupRole::AssistantOrganizer->value,
        'joined_at' => now(),
    ]);

    $user = User::factory()->create();
    $joinRequest = GroupJoinRequest::create([
        'group_id' => $group->id,
        'user_id' => $user->id,
        'status' => JoinRequestStatus::Pending,
    ]);

    $this->actingAs($assistant)
        ->post(route('groups.join-requests.approve', [$group, $joinRequest]))
        ->assertRedirect(route('groups.show', $group));

    $this->assertDatabaseHas('group_join_requests', [
        'id' => $joinRequest->id,
        'status' => JoinRequestStatus::Approved->value,
        'reviewed_by' => $assistant->id,
    ]);
});
