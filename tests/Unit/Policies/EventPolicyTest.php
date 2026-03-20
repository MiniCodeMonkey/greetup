<?php

use App\Enums\GroupRole;
use App\Models\Event;
use App\Models\Group;
use App\Models\User;
use App\Policies\EventPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);

    $this->policy = new EventPolicy;
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

    // Create an event in this group with a host
    $this->host = User::factory()->create();
    $this->group->members()->attach($this->host, [
        'role' => GroupRole::Member,
        'joined_at' => now(),
    ]);

    $this->event = Event::factory()->for($this->group)->create();
    $this->event->hosts()->attach($this->host);

    // Create a second event in the same group (host of event A should NOT have access to event B)
    $this->eventB = Event::factory()->for($this->group)->create();

    // Reload to clear cached relations
    $this->group->unsetRelation('members');
});

// --- view ---

it('allows any user to view an event', function (): void {
    expect($this->policy->view($this->nonMember, $this->event))->toBeTrue()
        ->and($this->policy->view($this->member, $this->event))->toBeTrue()
        ->and($this->policy->view($this->organizer, $this->event))->toBeTrue();
});

// --- create ---

it('allows event_organizer+ to create events', function (User $user, bool $expected): void {
    expect($this->policy->create($user, $this->group))->toBe($expected);
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

// --- update ---

it('allows event_organizer+ to update any group event', function (User $user, bool $expected): void {
    expect($this->policy->update($user, $this->event))->toBe($expected);
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

it('allows host to update their own event', function (): void {
    expect($this->policy->update($this->host, $this->event))->toBeTrue();
});

it('denies host from updating a different event', function (): void {
    expect($this->policy->update($this->host, $this->eventB))->toBeFalse();
});

// --- cancel ---

it('allows event_organizer+ to cancel events', function (User $user, bool $expected): void {
    expect($this->policy->cancel($user, $this->event))->toBe($expected);
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

// --- manageAttendees ---

it('allows event_organizer+ to manage attendees', function (User $user, bool $expected): void {
    expect($this->policy->manageAttendees($user, $this->event))->toBe($expected);
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

it('allows host to manage attendees for their own event', function (): void {
    expect($this->policy->manageAttendees($this->host, $this->event))->toBeTrue();
});

it('denies host from managing attendees of a different event', function (): void {
    expect($this->policy->manageAttendees($this->host, $this->eventB))->toBeFalse();
});

// --- checkIn ---

it('allows event_organizer+ to check in attendees', function (User $user, bool $expected): void {
    expect($this->policy->checkIn($user, $this->event))->toBe($expected);
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

it('allows host to check in attendees for their own event', function (): void {
    expect($this->policy->checkIn($this->host, $this->event))->toBeTrue();
});

it('denies host from checking in attendees of a different event', function (): void {
    expect($this->policy->checkIn($this->host, $this->eventB))->toBeFalse();
});

// --- rsvp ---

it('allows verified members to RSVP', function (): void {
    expect($this->policy->rsvp($this->member, $this->event))->toBeTrue();
});

it('denies non-members from RSVPing', function (): void {
    expect($this->policy->rsvp($this->nonMember, $this->event))->toBeFalse();
});

it('denies unverified users from RSVPing', function (): void {
    // Make unverified user a member
    $this->group->members()->attach($this->unverifiedUser, [
        'role' => GroupRole::Member,
        'joined_at' => now(),
    ]);
    $this->group->unsetRelation('members');

    expect($this->policy->rsvp($this->unverifiedUser, $this->event))->toBeFalse();
});

it('denies banned members from RSVPing', function (): void {
    expect($this->policy->rsvp($this->bannedMember, $this->event))->toBeFalse();
});

// --- suspended user denied everything ---

it('denies suspended user all event actions', function (): void {
    // Make the suspended user a host and organizer to prove suspension overrides
    $this->group->members()->attach($this->suspendedUser, [
        'role' => GroupRole::Organizer,
        'joined_at' => now(),
    ]);
    $this->event->hosts()->attach($this->suspendedUser);
    $this->group->unsetRelation('members');

    expect($this->policy->view($this->suspendedUser, $this->event))->toBeTrue() // view is always true
        ->and($this->policy->create($this->suspendedUser, $this->group))->toBeFalse()
        ->and($this->policy->update($this->suspendedUser, $this->event))->toBeFalse()
        ->and($this->policy->cancel($this->suspendedUser, $this->event))->toBeFalse()
        ->and($this->policy->manageAttendees($this->suspendedUser, $this->event))->toBeFalse()
        ->and($this->policy->checkIn($this->suspendedUser, $this->event))->toBeFalse()
        ->and($this->policy->rsvp($this->suspendedUser, $this->event))->toBeFalse();
});

// --- host-specific scoping ---

it('host of event A cannot edit event B', function (): void {
    expect($this->policy->update($this->host, $this->event))->toBeTrue()
        ->and($this->policy->update($this->host, $this->eventB))->toBeFalse()
        ->and($this->policy->manageAttendees($this->host, $this->event))->toBeTrue()
        ->and($this->policy->manageAttendees($this->host, $this->eventB))->toBeFalse()
        ->and($this->policy->checkIn($this->host, $this->event))->toBeTrue()
        ->and($this->policy->checkIn($this->host, $this->eventB))->toBeFalse();
});
