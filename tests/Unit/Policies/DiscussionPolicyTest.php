<?php

use App\Enums\GroupRole;
use App\Models\Discussion;
use App\Models\DiscussionReply;
use App\Models\Group;
use App\Models\User;
use App\Policies\DiscussionPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);

    $this->policy = new DiscussionPolicy;
    $this->group = Group::factory()->create();

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

    // Create a discussion authored by the member
    $this->discussion = Discussion::factory()->for($this->group)->for($this->member, 'user')->create();

    // Create a locked discussion
    $this->lockedDiscussion = Discussion::factory()->for($this->group)->for($this->member, 'user')->create([
        'is_locked' => true,
    ]);

    // Reload to clear cached relations
    $this->group->unsetRelation('members');
});

// --- create ---

it('allows any group member to create discussions', function (User $user, bool $expected): void {
    expect($this->policy->create($user, $this->group))->toBe($expected);
})->with(function (): array {
    return [
        'member allowed' => [fn () => $this->member, true],
        'event_organizer allowed' => [fn () => $this->eventOrganizer, true],
        'assistant_organizer allowed' => [fn () => $this->assistantOrganizer, true],
        'co_organizer allowed' => [fn () => $this->coOrganizer, true],
        'organizer allowed' => [fn () => $this->organizer, true],
        'non-member denied' => [fn () => $this->nonMember, false],
        'banned member denied' => [fn () => $this->bannedMember, false],
    ];
});

// --- reply ---

it('allows any group member to reply to a discussion', function (User $user, bool $expected): void {
    expect($this->policy->reply($user, $this->discussion))->toBe($expected);
})->with(function (): array {
    return [
        'member allowed' => [fn () => $this->member, true],
        'event_organizer allowed' => [fn () => $this->eventOrganizer, true],
        'assistant_organizer allowed' => [fn () => $this->assistantOrganizer, true],
        'co_organizer allowed' => [fn () => $this->coOrganizer, true],
        'organizer allowed' => [fn () => $this->organizer, true],
        'non-member denied' => [fn () => $this->nonMember, false],
        'banned member denied' => [fn () => $this->bannedMember, false],
    ];
});

it('denies replying to a locked discussion', function (): void {
    expect($this->policy->reply($this->member, $this->lockedDiscussion))->toBeFalse()
        ->and($this->policy->reply($this->organizer, $this->lockedDiscussion))->toBeFalse();
});

// --- pin ---

it('allows co_organizer+ to pin/unpin discussions', function (User $user, bool $expected): void {
    expect($this->policy->pin($user, $this->discussion))->toBe($expected);
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

// --- lock ---

it('allows co_organizer+ to lock/unlock discussions', function (User $user, bool $expected): void {
    expect($this->policy->lock($user, $this->discussion))->toBe($expected);
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

// --- delete discussion ---

it('allows co_organizer+ to delete any discussion', function (User $user, bool $expected): void {
    expect($this->policy->delete($user, $this->discussion))->toBe($expected);
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

// --- deleteReply ---

it('allows authors to delete their own replies', function (): void {
    $reply = DiscussionReply::factory()
        ->for($this->discussion)
        ->for($this->member, 'user')
        ->create();

    expect($this->policy->deleteReply($this->member, $reply))->toBeTrue();
});

it('denies authors from deleting other users replies', function (): void {
    $reply = DiscussionReply::factory()
        ->for($this->discussion)
        ->for($this->organizer, 'user')
        ->create();

    expect($this->policy->deleteReply($this->member, $reply))->toBeFalse();
});

it('allows co_organizer+ to delete any reply', function (User $user, bool $expected): void {
    $reply = DiscussionReply::factory()
        ->for($this->discussion)
        ->for($this->member, 'user')
        ->create();

    expect($this->policy->deleteReply($user, $reply))->toBe($expected);
})->with(function (): array {
    return [
        'co_organizer allowed' => [fn () => $this->coOrganizer, true],
        'organizer allowed' => [fn () => $this->organizer, true],
        'event_organizer denied' => [fn () => $this->eventOrganizer, false],
        'assistant_organizer denied' => [fn () => $this->assistantOrganizer, false],
        'non-member denied' => [fn () => $this->nonMember, false],
    ];
});

// --- suspended user denied everything ---

it('denies suspended user all discussion actions', function (): void {
    $this->group->members()->attach($this->suspendedUser, [
        'role' => GroupRole::Organizer,
        'joined_at' => now(),
    ]);
    $this->group->unsetRelation('members');

    $reply = DiscussionReply::factory()
        ->for($this->discussion)
        ->for($this->suspendedUser, 'user')
        ->create();

    expect($this->policy->create($this->suspendedUser, $this->group))->toBeFalse()
        ->and($this->policy->reply($this->suspendedUser, $this->discussion))->toBeFalse()
        ->and($this->policy->pin($this->suspendedUser, $this->discussion))->toBeFalse()
        ->and($this->policy->lock($this->suspendedUser, $this->discussion))->toBeFalse()
        ->and($this->policy->delete($this->suspendedUser, $this->discussion))->toBeFalse()
        ->and($this->policy->deleteReply($this->suspendedUser, $reply))->toBeFalse();
});
