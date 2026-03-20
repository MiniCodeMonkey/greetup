<?php

use App\Enums\GroupRole;
use App\Models\Event;
use App\Models\EventChatMessage;
use App\Models\Group;
use App\Models\Rsvp;
use App\Models\User;
use App\Policies\EventChatPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);

    $this->policy = new EventChatPolicy;
    $this->group = Group::factory()->create();
    $this->event = Event::factory()->for($this->group)->create(['is_chat_enabled' => true]);

    $this->nonMember = User::factory()->create();
    $this->suspendedUser = User::factory()->suspended()->create();

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

    $this->organizer = User::factory()->create();
    $this->group->members()->attach($this->organizer, [
        'role' => GroupRole::Organizer,
        'joined_at' => now(),
    ]);

    // RSVP'd user who is NOT a group member
    $this->rsvpGoingNonMember = User::factory()->create();
    Rsvp::factory()->for($this->event)->for($this->rsvpGoingNonMember)->going()->create();

    // RSVP Going member
    Rsvp::factory()->for($this->event)->for($this->member)->going()->create();

    $this->group->unsetRelation('members');
});

// --- send ---

it('allows RSVP Going user to send messages', function (): void {
    expect($this->policy->send($this->rsvpGoingNonMember, $this->event))->toBeTrue();
});

it('allows group member (non-RSVP) to send messages', function (): void {
    // eventOrganizer has no RSVP but is a group member
    expect($this->policy->send($this->eventOrganizer, $this->event))->toBeTrue();
});

it('denies non-member non-RSVP user from sending messages', function (): void {
    expect($this->policy->send($this->nonMember, $this->event))->toBeFalse();
});

it('denies sending when chat is disabled', function (): void {
    $disabledEvent = Event::factory()->for($this->group)->create(['is_chat_enabled' => false]);
    Rsvp::factory()->for($disabledEvent)->for($this->member)->going()->create();

    expect($this->policy->send($this->member, $disabledEvent))->toBeFalse()
        ->and($this->policy->send($this->organizer, $disabledEvent))->toBeFalse();
});

// --- edit ---

it('allows user to edit their own message', function (): void {
    $message = EventChatMessage::factory()->for($this->event)->for($this->member, 'user')->create();

    expect($this->policy->edit($this->member, $message))->toBeTrue();
});

it('denies user from editing another users message', function (): void {
    $message = EventChatMessage::factory()->for($this->event)->for($this->member, 'user')->create();

    expect($this->policy->edit($this->eventOrganizer, $message))->toBeFalse()
        ->and($this->policy->edit($this->organizer, $message))->toBeFalse();
});

// --- delete ---

it('allows user to delete their own message', function (): void {
    $message = EventChatMessage::factory()->for($this->event)->for($this->member, 'user')->create();

    expect($this->policy->delete($this->member, $message))->toBeTrue();
});

it('allows event_organizer+ to delete others messages', function (User $user, bool $expected): void {
    $message = EventChatMessage::factory()->for($this->event)->for($this->member, 'user')->create();

    expect($this->policy->delete($user, $message))->toBe($expected);
})->with(function (): array {
    return [
        'event_organizer allowed' => [fn () => $this->eventOrganizer, true],
        'organizer allowed' => [fn () => $this->organizer, true],
        'non-member denied' => [fn () => $this->nonMember, false],
    ];
});

// --- chat disabled blocks edit/delete too ---

it('denies edit when chat is disabled', function (): void {
    $disabledEvent = Event::factory()->for($this->group)->create(['is_chat_enabled' => false]);
    $message = EventChatMessage::factory()->for($disabledEvent)->for($this->member, 'user')->create();

    expect($this->policy->edit($this->member, $message))->toBeFalse();
});

it('denies delete when chat is disabled', function (): void {
    $disabledEvent = Event::factory()->for($this->group)->create(['is_chat_enabled' => false]);
    $message = EventChatMessage::factory()->for($disabledEvent)->for($this->member, 'user')->create();

    expect($this->policy->delete($this->member, $message))->toBeFalse()
        ->and($this->policy->delete($this->organizer, $message))->toBeFalse();
});

// --- suspended user denied everything ---

it('denies suspended user all chat actions', function (): void {
    $this->group->members()->attach($this->suspendedUser, [
        'role' => GroupRole::Organizer,
        'joined_at' => now(),
    ]);
    $this->group->unsetRelation('members');
    Rsvp::factory()->for($this->event)->for($this->suspendedUser)->going()->create();

    $message = EventChatMessage::factory()->for($this->event)->for($this->suspendedUser, 'user')->create();

    expect($this->policy->send($this->suspendedUser, $this->event))->toBeFalse()
        ->and($this->policy->edit($this->suspendedUser, $message))->toBeFalse()
        ->and($this->policy->delete($this->suspendedUser, $message))->toBeFalse();
});
