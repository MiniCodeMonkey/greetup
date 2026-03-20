<?php

use App\Enums\GroupRole;
use App\Models\Group;
use App\Models\User;
use App\Policies\GroupPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);

    $this->policy = new GroupPolicy;
    $this->group = Group::factory()->create();

    $this->nonMember = User::factory()->create();
    $this->suspendedUser = User::factory()->suspended()->create();
    $this->unverifiedUser = User::factory()->unverified()->create();

    // Create members with each role
    $this->member = User::factory()->create();
    $this->group->members()->attach($this->member, [
        'role' => GroupRole::Member,
        'joined_at' => now(),
    ]);

    $this->eventOrganizer = User::factory()->create();
    $this->group->members()->attach($this->eventOrganizer, [
        'role' => GroupRole::EventOrganizer,
        'joined_at' => now(),
    ]);

    $this->assistantOrganizer = User::factory()->create();
    $this->group->members()->attach($this->assistantOrganizer, [
        'role' => GroupRole::AssistantOrganizer,
        'joined_at' => now(),
    ]);

    $this->coOrganizer = User::factory()->create();
    $this->group->members()->attach($this->coOrganizer, [
        'role' => GroupRole::CoOrganizer,
        'joined_at' => now(),
    ]);

    $this->organizer = User::factory()->create();
    $this->group->members()->attach($this->organizer, [
        'role' => GroupRole::Organizer,
        'joined_at' => now(),
    ]);

    $this->bannedMember = User::factory()->create();
    $this->group->members()->attach($this->bannedMember, [
        'role' => GroupRole::Member,
        'joined_at' => now(),
        'is_banned' => true,
        'banned_at' => now(),
        'banned_reason' => 'Test ban',
    ]);

    // Reload to clear cached relations
    $this->group->unsetRelation('members');
});

// --- view ---

it('allows any user to view a group', function (): void {
    expect($this->policy->view($this->nonMember, $this->group))->toBeTrue()
        ->and($this->policy->view($this->member, $this->group))->toBeTrue()
        ->and($this->policy->view($this->eventOrganizer, $this->group))->toBeTrue()
        ->and($this->policy->view($this->assistantOrganizer, $this->group))->toBeTrue()
        ->and($this->policy->view($this->coOrganizer, $this->group))->toBeTrue()
        ->and($this->policy->view($this->organizer, $this->group))->toBeTrue();
});

// --- join ---

it('allows verified non-member to join', function (): void {
    expect($this->policy->join($this->nonMember, $this->group))->toBeTrue();
});

it('denies unverified user from joining', function (): void {
    expect($this->policy->join($this->unverifiedUser, $this->group))->toBeFalse();
});

it('denies existing member from joining', function (): void {
    expect($this->policy->join($this->member, $this->group))->toBeFalse();
});

it('denies banned member from joining', function (): void {
    expect($this->policy->join($this->bannedMember, $this->group))->toBeFalse();
});

it('denies suspended user from joining', function (): void {
    expect($this->policy->join($this->suspendedUser, $this->group))->toBeFalse();
});

// --- leave ---

it('allows members to leave except organizer', function (): void {
    expect($this->policy->leave($this->member, $this->group))->toBeTrue()
        ->and($this->policy->leave($this->eventOrganizer, $this->group))->toBeTrue()
        ->and($this->policy->leave($this->assistantOrganizer, $this->group))->toBeTrue()
        ->and($this->policy->leave($this->coOrganizer, $this->group))->toBeTrue()
        ->and($this->policy->leave($this->organizer, $this->group))->toBeFalse();
});

it('denies non-member from leaving', function (): void {
    expect($this->policy->leave($this->nonMember, $this->group))->toBeFalse();
});

it('denies banned member from leaving', function (): void {
    expect($this->policy->leave($this->bannedMember, $this->group))->toBeFalse();
});

// --- event_organizer+ actions ---

it('allows event_organizer+ to createEvent', function (User $user, bool $expected): void {
    expect($this->policy->createEvent($user, $this->group))->toBe($expected);
})->with(function (): array {
    return [
        'member denied' => [fn () => $this->member, false],
        'event_organizer allowed' => [fn () => $this->eventOrganizer, true],
        'assistant_organizer allowed' => [fn () => $this->assistantOrganizer, true],
        'co_organizer allowed' => [fn () => $this->coOrganizer, true],
        'organizer allowed' => [fn () => $this->organizer, true],
        'non-member denied' => [fn () => $this->nonMember, false],
    ];
});

it('allows event_organizer+ to editAnyEvent', function (User $user, bool $expected): void {
    expect($this->policy->editAnyEvent($user, $this->group))->toBe($expected);
})->with(function (): array {
    return [
        'member denied' => [fn () => $this->member, false],
        'event_organizer allowed' => [fn () => $this->eventOrganizer, true],
        'assistant_organizer allowed' => [fn () => $this->assistantOrganizer, true],
        'co_organizer allowed' => [fn () => $this->coOrganizer, true],
        'organizer allowed' => [fn () => $this->organizer, true],
        'non-member denied' => [fn () => $this->nonMember, false],
    ];
});

it('allows event_organizer+ to cancelEvent', function (User $user, bool $expected): void {
    expect($this->policy->cancelEvent($user, $this->group))->toBe($expected);
})->with(function (): array {
    return [
        'member denied' => [fn () => $this->member, false],
        'event_organizer allowed' => [fn () => $this->eventOrganizer, true],
        'assistant_organizer allowed' => [fn () => $this->assistantOrganizer, true],
        'co_organizer allowed' => [fn () => $this->coOrganizer, true],
        'organizer allowed' => [fn () => $this->organizer, true],
        'non-member denied' => [fn () => $this->nonMember, false],
    ];
});

it('allows event_organizer+ to manageRsvps', function (User $user, bool $expected): void {
    expect($this->policy->manageRsvps($user, $this->group))->toBe($expected);
})->with(function (): array {
    return [
        'member denied' => [fn () => $this->member, false],
        'event_organizer allowed' => [fn () => $this->eventOrganizer, true],
        'assistant_organizer allowed' => [fn () => $this->assistantOrganizer, true],
        'co_organizer allowed' => [fn () => $this->coOrganizer, true],
        'organizer allowed' => [fn () => $this->organizer, true],
        'non-member denied' => [fn () => $this->nonMember, false],
    ];
});

it('allows event_organizer+ to checkInAttendees', function (User $user, bool $expected): void {
    expect($this->policy->checkInAttendees($user, $this->group))->toBe($expected);
})->with(function (): array {
    return [
        'member denied' => [fn () => $this->member, false],
        'event_organizer allowed' => [fn () => $this->eventOrganizer, true],
        'assistant_organizer allowed' => [fn () => $this->assistantOrganizer, true],
        'co_organizer allowed' => [fn () => $this->coOrganizer, true],
        'organizer allowed' => [fn () => $this->organizer, true],
        'non-member denied' => [fn () => $this->nonMember, false],
    ];
});

it('allows event_organizer+ to sendGroupMessages', function (User $user, bool $expected): void {
    expect($this->policy->sendGroupMessages($user, $this->group))->toBe($expected);
})->with(function (): array {
    return [
        'member denied' => [fn () => $this->member, false],
        'event_organizer allowed' => [fn () => $this->eventOrganizer, true],
        'assistant_organizer allowed' => [fn () => $this->assistantOrganizer, true],
        'co_organizer allowed' => [fn () => $this->coOrganizer, true],
        'organizer allowed' => [fn () => $this->organizer, true],
        'non-member denied' => [fn () => $this->nonMember, false],
    ];
});

it('allows event_organizer+ to assignEventHosts', function (User $user, bool $expected): void {
    expect($this->policy->assignEventHosts($user, $this->group))->toBe($expected);
})->with(function (): array {
    return [
        'member denied' => [fn () => $this->member, false],
        'event_organizer allowed' => [fn () => $this->eventOrganizer, true],
        'assistant_organizer allowed' => [fn () => $this->assistantOrganizer, true],
        'co_organizer allowed' => [fn () => $this->coOrganizer, true],
        'organizer allowed' => [fn () => $this->organizer, true],
        'non-member denied' => [fn () => $this->nonMember, false],
    ];
});

// --- assistant_organizer+ actions ---

it('allows assistant_organizer+ to acceptRequests', function (User $user, bool $expected): void {
    expect($this->policy->acceptRequests($user, $this->group))->toBe($expected);
})->with(function (): array {
    return [
        'member denied' => [fn () => $this->member, false],
        'event_organizer denied' => [fn () => $this->eventOrganizer, false],
        'assistant_organizer allowed' => [fn () => $this->assistantOrganizer, true],
        'co_organizer allowed' => [fn () => $this->coOrganizer, true],
        'organizer allowed' => [fn () => $this->organizer, true],
        'non-member denied' => [fn () => $this->nonMember, false],
    ];
});

it('allows assistant_organizer+ to removeMembers', function (User $user, bool $expected): void {
    expect($this->policy->removeMembers($user, $this->group))->toBe($expected);
})->with(function (): array {
    return [
        'member denied' => [fn () => $this->member, false],
        'event_organizer denied' => [fn () => $this->eventOrganizer, false],
        'assistant_organizer allowed' => [fn () => $this->assistantOrganizer, true],
        'co_organizer allowed' => [fn () => $this->coOrganizer, true],
        'organizer allowed' => [fn () => $this->organizer, true],
        'non-member denied' => [fn () => $this->nonMember, false],
    ];
});

it('allows assistant_organizer+ to banMembers', function (User $user, bool $expected): void {
    expect($this->policy->banMembers($user, $this->group))->toBe($expected);
})->with(function (): array {
    return [
        'member denied' => [fn () => $this->member, false],
        'event_organizer denied' => [fn () => $this->eventOrganizer, false],
        'assistant_organizer allowed' => [fn () => $this->assistantOrganizer, true],
        'co_organizer allowed' => [fn () => $this->coOrganizer, true],
        'organizer allowed' => [fn () => $this->organizer, true],
        'non-member denied' => [fn () => $this->nonMember, false],
    ];
});

// --- co_organizer+ actions ---

it('allows co_organizer+ to editSettings', function (User $user, bool $expected): void {
    expect($this->policy->editSettings($user, $this->group))->toBe($expected);
})->with(function (): array {
    return [
        'member denied' => [fn () => $this->member, false],
        'event_organizer denied' => [fn () => $this->eventOrganizer, false],
        'assistant_organizer denied' => [fn () => $this->assistantOrganizer, false],
        'co_organizer allowed' => [fn () => $this->coOrganizer, true],
        'organizer allowed' => [fn () => $this->organizer, true],
        'non-member denied' => [fn () => $this->nonMember, false],
    ];
});

it('allows co_organizer+ to manageLeadership', function (User $user, bool $expected): void {
    expect($this->policy->manageLeadership($user, $this->group))->toBe($expected);
})->with(function (): array {
    return [
        'member denied' => [fn () => $this->member, false],
        'event_organizer denied' => [fn () => $this->eventOrganizer, false],
        'assistant_organizer denied' => [fn () => $this->assistantOrganizer, false],
        'co_organizer allowed' => [fn () => $this->coOrganizer, true],
        'organizer allowed' => [fn () => $this->organizer, true],
        'non-member denied' => [fn () => $this->nonMember, false],
    ];
});

it('allows co_organizer+ to viewAnalytics', function (User $user, bool $expected): void {
    expect($this->policy->viewAnalytics($user, $this->group))->toBe($expected);
})->with(function (): array {
    return [
        'member denied' => [fn () => $this->member, false],
        'event_organizer denied' => [fn () => $this->eventOrganizer, false],
        'assistant_organizer denied' => [fn () => $this->assistantOrganizer, false],
        'co_organizer allowed' => [fn () => $this->coOrganizer, true],
        'organizer allowed' => [fn () => $this->organizer, true],
        'non-member denied' => [fn () => $this->nonMember, false],
    ];
});

// --- organizer only actions ---

it('allows only organizer to delete', function (User $user, bool $expected): void {
    expect($this->policy->delete($user, $this->group))->toBe($expected);
})->with(function (): array {
    return [
        'member denied' => [fn () => $this->member, false],
        'event_organizer denied' => [fn () => $this->eventOrganizer, false],
        'assistant_organizer denied' => [fn () => $this->assistantOrganizer, false],
        'co_organizer denied' => [fn () => $this->coOrganizer, false],
        'organizer allowed' => [fn () => $this->organizer, true],
        'non-member denied' => [fn () => $this->nonMember, false],
    ];
});

it('allows only organizer to transferOwnership', function (User $user, bool $expected): void {
    expect($this->policy->transferOwnership($user, $this->group))->toBe($expected);
})->with(function (): array {
    return [
        'member denied' => [fn () => $this->member, false],
        'event_organizer denied' => [fn () => $this->eventOrganizer, false],
        'assistant_organizer denied' => [fn () => $this->assistantOrganizer, false],
        'co_organizer denied' => [fn () => $this->coOrganizer, false],
        'organizer allowed' => [fn () => $this->organizer, true],
        'non-member denied' => [fn () => $this->nonMember, false],
    ];
});

// --- suspended user denied everything ---

it('denies suspended user all actions', function (): void {
    // Make the suspended user a member with organizer role to prove suspension overrides role
    $this->group->members()->attach($this->suspendedUser, [
        'role' => GroupRole::Organizer,
        'joined_at' => now(),
    ]);
    $this->group->unsetRelation('members');

    expect($this->policy->join($this->suspendedUser, $this->group))->toBeFalse()
        ->and($this->policy->leave($this->suspendedUser, $this->group))->toBeFalse()
        ->and($this->policy->createEvent($this->suspendedUser, $this->group))->toBeFalse()
        ->and($this->policy->editAnyEvent($this->suspendedUser, $this->group))->toBeFalse()
        ->and($this->policy->cancelEvent($this->suspendedUser, $this->group))->toBeFalse()
        ->and($this->policy->manageRsvps($this->suspendedUser, $this->group))->toBeFalse()
        ->and($this->policy->checkInAttendees($this->suspendedUser, $this->group))->toBeFalse()
        ->and($this->policy->sendGroupMessages($this->suspendedUser, $this->group))->toBeFalse()
        ->and($this->policy->assignEventHosts($this->suspendedUser, $this->group))->toBeFalse()
        ->and($this->policy->acceptRequests($this->suspendedUser, $this->group))->toBeFalse()
        ->and($this->policy->removeMembers($this->suspendedUser, $this->group))->toBeFalse()
        ->and($this->policy->banMembers($this->suspendedUser, $this->group))->toBeFalse()
        ->and($this->policy->editSettings($this->suspendedUser, $this->group))->toBeFalse()
        ->and($this->policy->manageLeadership($this->suspendedUser, $this->group))->toBeFalse()
        ->and($this->policy->viewAnalytics($this->suspendedUser, $this->group))->toBeFalse()
        ->and($this->policy->delete($this->suspendedUser, $this->group))->toBeFalse()
        ->and($this->policy->transferOwnership($this->suspendedUser, $this->group))->toBeFalse();
});

// --- non-member denied group actions ---

it('denies non-member all group actions', function (): void {
    expect($this->policy->leave($this->nonMember, $this->group))->toBeFalse()
        ->and($this->policy->createEvent($this->nonMember, $this->group))->toBeFalse()
        ->and($this->policy->editAnyEvent($this->nonMember, $this->group))->toBeFalse()
        ->and($this->policy->cancelEvent($this->nonMember, $this->group))->toBeFalse()
        ->and($this->policy->manageRsvps($this->nonMember, $this->group))->toBeFalse()
        ->and($this->policy->checkInAttendees($this->nonMember, $this->group))->toBeFalse()
        ->and($this->policy->sendGroupMessages($this->nonMember, $this->group))->toBeFalse()
        ->and($this->policy->assignEventHosts($this->nonMember, $this->group))->toBeFalse()
        ->and($this->policy->acceptRequests($this->nonMember, $this->group))->toBeFalse()
        ->and($this->policy->removeMembers($this->nonMember, $this->group))->toBeFalse()
        ->and($this->policy->banMembers($this->nonMember, $this->group))->toBeFalse()
        ->and($this->policy->editSettings($this->nonMember, $this->group))->toBeFalse()
        ->and($this->policy->manageLeadership($this->nonMember, $this->group))->toBeFalse()
        ->and($this->policy->viewAnalytics($this->nonMember, $this->group))->toBeFalse()
        ->and($this->policy->delete($this->nonMember, $this->group))->toBeFalse()
        ->and($this->policy->transferOwnership($this->nonMember, $this->group))->toBeFalse();
});

// --- role inheritance ---

it('co_organizer can do everything assistant_organizer can', function (): void {
    expect($this->policy->acceptRequests($this->coOrganizer, $this->group))->toBeTrue()
        ->and($this->policy->removeMembers($this->coOrganizer, $this->group))->toBeTrue()
        ->and($this->policy->banMembers($this->coOrganizer, $this->group))->toBeTrue()
        ->and($this->policy->createEvent($this->coOrganizer, $this->group))->toBeTrue()
        ->and($this->policy->editAnyEvent($this->coOrganizer, $this->group))->toBeTrue()
        ->and($this->policy->cancelEvent($this->coOrganizer, $this->group))->toBeTrue()
        ->and($this->policy->manageRsvps($this->coOrganizer, $this->group))->toBeTrue()
        ->and($this->policy->checkInAttendees($this->coOrganizer, $this->group))->toBeTrue()
        ->and($this->policy->sendGroupMessages($this->coOrganizer, $this->group))->toBeTrue()
        ->and($this->policy->assignEventHosts($this->coOrganizer, $this->group))->toBeTrue();
});

it('organizer can do everything co_organizer can', function (): void {
    expect($this->policy->editSettings($this->organizer, $this->group))->toBeTrue()
        ->and($this->policy->manageLeadership($this->organizer, $this->group))->toBeTrue()
        ->and($this->policy->viewAnalytics($this->organizer, $this->group))->toBeTrue()
        ->and($this->policy->acceptRequests($this->organizer, $this->group))->toBeTrue()
        ->and($this->policy->removeMembers($this->organizer, $this->group))->toBeTrue()
        ->and($this->policy->banMembers($this->organizer, $this->group))->toBeTrue()
        ->and($this->policy->createEvent($this->organizer, $this->group))->toBeTrue()
        ->and($this->policy->editAnyEvent($this->organizer, $this->group))->toBeTrue();
});

// --- banned member denied ---

it('denies banned member all group actions', function (): void {
    expect($this->policy->leave($this->bannedMember, $this->group))->toBeFalse()
        ->and($this->policy->createEvent($this->bannedMember, $this->group))->toBeFalse()
        ->and($this->policy->editAnyEvent($this->bannedMember, $this->group))->toBeFalse()
        ->and($this->policy->cancelEvent($this->bannedMember, $this->group))->toBeFalse()
        ->and($this->policy->manageRsvps($this->bannedMember, $this->group))->toBeFalse()
        ->and($this->policy->checkInAttendees($this->bannedMember, $this->group))->toBeFalse()
        ->and($this->policy->sendGroupMessages($this->bannedMember, $this->group))->toBeFalse()
        ->and($this->policy->assignEventHosts($this->bannedMember, $this->group))->toBeFalse()
        ->and($this->policy->acceptRequests($this->bannedMember, $this->group))->toBeFalse()
        ->and($this->policy->removeMembers($this->bannedMember, $this->group))->toBeFalse()
        ->and($this->policy->banMembers($this->bannedMember, $this->group))->toBeFalse()
        ->and($this->policy->editSettings($this->bannedMember, $this->group))->toBeFalse()
        ->and($this->policy->manageLeadership($this->bannedMember, $this->group))->toBeFalse()
        ->and($this->policy->viewAnalytics($this->bannedMember, $this->group))->toBeFalse()
        ->and($this->policy->delete($this->bannedMember, $this->group))->toBeFalse()
        ->and($this->policy->transferOwnership($this->bannedMember, $this->group))->toBeFalse();
});
