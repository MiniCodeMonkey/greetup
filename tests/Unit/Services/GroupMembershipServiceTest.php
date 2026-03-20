<?php

use App\Enums\GroupRole;
use App\Enums\JoinRequestStatus;
use App\Models\Group;
use App\Models\GroupJoinRequest;
use App\Models\User;
use App\Services\GroupMembershipService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);
    $this->service = new GroupMembershipService;
});

// --- Join Group ---

it('allows a user to join an open group', function (): void {
    $group = Group::factory()->create(['requires_approval' => false]);
    $user = User::factory()->create();

    $this->service->joinGroup($group, $user);

    expect($group->members()->where('user_id', $user->id)->exists())->toBeTrue();
    $pivot = $group->members()->where('user_id', $user->id)->first()->pivot;
    expect($pivot->role)->toBe(GroupRole::Member)
        ->and($pivot->joined_at)->not->toBeNull();
});

it('throws when user is already a member', function (): void {
    $group = Group::factory()->create(['requires_approval' => false]);
    $user = User::factory()->create();
    $group->members()->attach($user, ['role' => 'member', 'joined_at' => now()]);

    $this->service->joinGroup($group, $user);
})->throws(InvalidArgumentException::class, 'User is already a member of this group.');

it('throws when group requires approval', function (): void {
    $group = Group::factory()->create(['requires_approval' => true]);
    $user = User::factory()->create();

    $this->service->joinGroup($group, $user);
})->throws(InvalidArgumentException::class, 'This group requires approval to join.');

it('throws when group has reached member limit', function (): void {
    $group = Group::factory()->create(['requires_approval' => false, 'max_members' => 1]);
    $existingUser = User::factory()->create();
    $group->members()->attach($existingUser, ['role' => 'member', 'joined_at' => now()]);

    $newUser = User::factory()->create();

    $this->service->joinGroup($group, $newUser);
})->throws(InvalidArgumentException::class, 'This group has reached its member limit.');

// --- Request to Join ---

it('creates a join request for approval-required group', function (): void {
    $group = Group::factory()->create(['requires_approval' => true]);
    $user = User::factory()->create();

    $request = $this->service->requestToJoin($group, $user);

    expect($request->group_id)->toBe($group->id)
        ->and($request->user_id)->toBe($user->id)
        ->and($request->status)->toBe(JoinRequestStatus::Pending);
});

it('throws when requesting to join a group that does not require approval', function (): void {
    $group = Group::factory()->create(['requires_approval' => false]);
    $user = User::factory()->create();

    $this->service->requestToJoin($group, $user);
})->throws(InvalidArgumentException::class, 'This group does not require approval.');

it('throws when user already has a pending join request', function (): void {
    $group = Group::factory()->create(['requires_approval' => true]);
    $user = User::factory()->create();

    $this->service->requestToJoin($group, $user);
    $this->service->requestToJoin($group, $user);
})->throws(InvalidArgumentException::class, 'User already has a pending join request.');

// --- Approve Request ---

it('approves a pending join request and adds member', function (): void {
    $group = Group::factory()->create(['requires_approval' => true]);
    $user = User::factory()->create();
    $reviewer = User::factory()->create();

    $request = GroupJoinRequest::create([
        'group_id' => $group->id,
        'user_id' => $user->id,
        'status' => JoinRequestStatus::Pending,
    ]);

    $this->service->approveRequest($request, $reviewer);

    $request->refresh();
    expect($request->status)->toBe(JoinRequestStatus::Approved)
        ->and($request->reviewed_by)->toBe($reviewer->id)
        ->and($request->reviewed_at)->not->toBeNull()
        ->and($group->members()->where('user_id', $user->id)->exists())->toBeTrue();
});

it('throws when approving a non-pending request', function (): void {
    $group = Group::factory()->create(['requires_approval' => true]);
    $user = User::factory()->create();
    $reviewer = User::factory()->create();

    $request = GroupJoinRequest::create([
        'group_id' => $group->id,
        'user_id' => $user->id,
        'status' => JoinRequestStatus::Denied,
    ]);

    $this->service->approveRequest($request, $reviewer);
})->throws(InvalidArgumentException::class, 'Only pending requests can be approved.');

// --- Deny Request ---

it('denies a pending join request with reason', function (): void {
    $group = Group::factory()->create(['requires_approval' => true]);
    $user = User::factory()->create();
    $reviewer = User::factory()->create();

    $request = GroupJoinRequest::create([
        'group_id' => $group->id,
        'user_id' => $user->id,
        'status' => JoinRequestStatus::Pending,
    ]);

    $this->service->denyRequest($request, $reviewer, 'Not a fit');

    $request->refresh();
    expect($request->status)->toBe(JoinRequestStatus::Denied)
        ->and($request->reviewed_by)->toBe($reviewer->id)
        ->and($request->denial_reason)->toBe('Not a fit');
});

// --- Leave Group ---

it('allows a member to leave a group', function (): void {
    $group = Group::factory()->create();
    $user = User::factory()->create();
    $group->members()->attach($user, ['role' => 'member', 'joined_at' => now()]);

    $this->service->leaveGroup($group, $user);

    expect($group->members()->where('user_id', $user->id)->exists())->toBeFalse();
});

it('throws when non-member tries to leave', function (): void {
    $group = Group::factory()->create();
    $user = User::factory()->create();

    $this->service->leaveGroup($group, $user);
})->throws(InvalidArgumentException::class, 'User is not a member of this group.');

it('throws when organizer tries to leave without transferring ownership', function (): void {
    $organizer = User::factory()->create();
    $group = Group::factory()->create(['organizer_id' => $organizer->id]);
    $group->members()->attach($organizer, ['role' => 'organizer', 'joined_at' => now()]);

    $this->service->leaveGroup($group, $organizer);
})->throws(InvalidArgumentException::class, 'The group organizer must transfer ownership before leaving.');

// --- Change Role ---

it('changes a member role', function (): void {
    $group = Group::factory()->create();
    $user = User::factory()->create();
    $group->members()->attach($user, ['role' => 'member', 'joined_at' => now()]);

    $this->service->changeRole($group, $user, GroupRole::CoOrganizer);

    $pivot = $group->members()->where('user_id', $user->id)->first()->pivot;
    expect($pivot->role)->toBe(GroupRole::CoOrganizer);
});

it('throws when changing role for non-member', function (): void {
    $group = Group::factory()->create();
    $user = User::factory()->create();

    $this->service->changeRole($group, $user, GroupRole::CoOrganizer);
})->throws(InvalidArgumentException::class, 'User is not a member of this group.');

// --- Is Member ---

it('returns true when user is a member', function (): void {
    $group = Group::factory()->create();
    $user = User::factory()->create();
    $group->members()->attach($user, ['role' => 'member', 'joined_at' => now()]);

    expect($this->service->isMember($group, $user))->toBeTrue();
});

it('returns false when user is not a member', function (): void {
    $group = Group::factory()->create();
    $user = User::factory()->create();

    expect($this->service->isMember($group, $user))->toBeFalse();
});
