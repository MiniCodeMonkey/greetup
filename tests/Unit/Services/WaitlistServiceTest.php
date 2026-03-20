<?php

use App\Enums\RsvpStatus;
use App\Models\Event;
use App\Models\Group;
use App\Models\Rsvp;
use App\Models\User;
use App\Notifications\PromotedFromWaitlist;
use App\Services\WaitlistService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);
    $this->service = new WaitlistService;
});

function createEventWithWaitlist(array $eventAttributes = [], int $waitlistedCount = 1, int $guestCount = 0): array
{
    $group = Group::factory()->create();
    $event = Event::factory()->published()->create(array_merge(
        ['group_id' => $group->id, 'starts_at' => now()->addDays(7)],
        $eventAttributes
    ));

    $waitlisted = [];

    for ($i = 0; $i < $waitlistedCount; $i++) {
        $user = User::factory()->create();
        $group->members()->attach($user, ['role' => 'member', 'joined_at' => now()]);

        $rsvp = Rsvp::create([
            'event_id' => $event->id,
            'user_id' => $user->id,
            'status' => RsvpStatus::Waitlisted,
            'guest_count' => $guestCount,
            'waitlisted_at' => now()->addSeconds($i),
        ]);

        $waitlisted[] = $rsvp;
    }

    return [$event, $waitlisted, $group];
}

// FIFO promotion

it('promotes the earliest waitlisted member first (FIFO)', function (): void {
    Notification::fake();

    [$event, $waitlisted] = createEventWithWaitlist(['rsvp_limit' => 5], 3);

    $promoted = $this->service->promoteNext($event);

    expect($promoted)->not->toBeNull()
        ->and($promoted->id)->toBe($waitlisted[0]->id)
        ->and($promoted->status)->toBe(RsvpStatus::Going)
        ->and($promoted->waitlisted_at)->toBeNull();
});

it('returns the promoted Rsvp model', function (): void {
    Notification::fake();

    [$event, $waitlisted] = createEventWithWaitlist(['rsvp_limit' => 5], 1);

    $promoted = $this->service->promoteNext($event);

    expect($promoted)->toBeInstanceOf(Rsvp::class)
        ->and($promoted->status)->toBe(RsvpStatus::Going);
});

// Guest count skipping

it('skips waitlisted members whose guest count exceeds available spots', function (): void {
    Notification::fake();

    $group = Group::factory()->create();
    $event = Event::factory()->published()->create([
        'group_id' => $group->id,
        'starts_at' => now()->addDays(7),
        'rsvp_limit' => 3,
    ]);

    // Fill 1 spot so only 2 remain
    $goingUser = User::factory()->create();
    $group->members()->attach($goingUser, ['role' => 'member', 'joined_at' => now()]);
    Rsvp::create([
        'event_id' => $event->id,
        'user_id' => $goingUser->id,
        'status' => RsvpStatus::Going,
        'guest_count' => 0,
    ]);

    // Waitlisted member needing 3 spots (1 + 2 guests) — should be skipped
    $bigParty = User::factory()->create();
    $group->members()->attach($bigParty, ['role' => 'member', 'joined_at' => now()]);
    Rsvp::create([
        'event_id' => $event->id,
        'user_id' => $bigParty->id,
        'status' => RsvpStatus::Waitlisted,
        'guest_count' => 2,
        'waitlisted_at' => now(),
    ]);

    // Waitlisted member needing 1 spot — should be promoted
    $soloUser = User::factory()->create();
    $group->members()->attach($soloUser, ['role' => 'member', 'joined_at' => now()]);
    $soloRsvp = Rsvp::create([
        'event_id' => $event->id,
        'user_id' => $soloUser->id,
        'status' => RsvpStatus::Waitlisted,
        'guest_count' => 0,
        'waitlisted_at' => now()->addSecond(),
    ]);

    $promoted = $this->service->promoteNext($event);

    expect($promoted)->not->toBeNull()
        ->and($promoted->id)->toBe($soloRsvp->id)
        ->and($promoted->user_id)->toBe($soloUser->id);

    // Verify the big party member is still waitlisted
    expect($bigParty->rsvps()->where('event_id', $event->id)->first()->status)
        ->toBe(RsvpStatus::Waitlisted);
});

// Revisiting skipped members when more spots open

it('promotes multiple waitlisted members when multiple spots open via promoteAll', function (): void {
    Notification::fake();

    $group = Group::factory()->create();
    $event = Event::factory()->published()->create([
        'group_id' => $group->id,
        'starts_at' => now()->addDays(7),
        'rsvp_limit' => 5,
    ]);

    // Waitlisted: first has 2 guests (needs 3), second has 0 guests (needs 1), third has 0 guests (needs 1)
    $userA = User::factory()->create();
    $group->members()->attach($userA, ['role' => 'member', 'joined_at' => now()]);
    Rsvp::create([
        'event_id' => $event->id,
        'user_id' => $userA->id,
        'status' => RsvpStatus::Waitlisted,
        'guest_count' => 2,
        'waitlisted_at' => now(),
    ]);

    $userB = User::factory()->create();
    $group->members()->attach($userB, ['role' => 'member', 'joined_at' => now()]);
    Rsvp::create([
        'event_id' => $event->id,
        'user_id' => $userB->id,
        'status' => RsvpStatus::Waitlisted,
        'guest_count' => 0,
        'waitlisted_at' => now()->addSecond(),
    ]);

    $userC = User::factory()->create();
    $group->members()->attach($userC, ['role' => 'member', 'joined_at' => now()]);
    Rsvp::create([
        'event_id' => $event->id,
        'user_id' => $userC->id,
        'status' => RsvpStatus::Waitlisted,
        'guest_count' => 0,
        'waitlisted_at' => now()->addSeconds(2),
    ]);

    $promoted = $this->service->promoteAll($event);

    expect($promoted)->toHaveCount(3);

    // All three should now be going
    expect(Rsvp::where('event_id', $event->id)->where('status', RsvpStatus::Going)->count())->toBe(3);
});

it('skips members too large and revisits them on subsequent passes', function (): void {
    Notification::fake();

    $group = Group::factory()->create();
    $event = Event::factory()->published()->create([
        'group_id' => $group->id,
        'starts_at' => now()->addDays(7),
        'rsvp_limit' => 2,
    ]);

    // userA needs 3 spots (1+2 guests) — will always be skipped with only 2 spots
    $userA = User::factory()->create();
    $group->members()->attach($userA, ['role' => 'member', 'joined_at' => now()]);
    Rsvp::create([
        'event_id' => $event->id,
        'user_id' => $userA->id,
        'status' => RsvpStatus::Waitlisted,
        'guest_count' => 2,
        'waitlisted_at' => now(),
    ]);

    // userB needs 1 spot — promoted
    $userB = User::factory()->create();
    $group->members()->attach($userB, ['role' => 'member', 'joined_at' => now()]);
    Rsvp::create([
        'event_id' => $event->id,
        'user_id' => $userB->id,
        'status' => RsvpStatus::Waitlisted,
        'guest_count' => 0,
        'waitlisted_at' => now()->addSecond(),
    ]);

    // userC needs 1 spot — promoted
    $userC = User::factory()->create();
    $group->members()->attach($userC, ['role' => 'member', 'joined_at' => now()]);
    Rsvp::create([
        'event_id' => $event->id,
        'user_id' => $userC->id,
        'status' => RsvpStatus::Waitlisted,
        'guest_count' => 0,
        'waitlisted_at' => now()->addSeconds(2),
    ]);

    $promoted = $this->service->promoteAll($event);

    // B and C promoted, A still waitlisted (revisited but couldn't fit)
    expect($promoted)->toHaveCount(2);
    expect(Rsvp::where('event_id', $event->id)->where('user_id', $userA->id)->first()->status)
        ->toBe(RsvpStatus::Waitlisted);
    expect(Rsvp::where('event_id', $event->id)->where('status', RsvpStatus::Going)->count())->toBe(2);
});

// Empty waitlist

it('returns null when waitlist is empty', function (): void {
    $group = Group::factory()->create();
    $event = Event::factory()->published()->create([
        'group_id' => $group->id,
        'starts_at' => now()->addDays(7),
        'rsvp_limit' => 10,
    ]);

    $result = $this->service->promoteNext($event);

    expect($result)->toBeNull();
});

// Cancelled event

it('returns null when event is cancelled', function (): void {
    [$event, $waitlisted] = createEventWithWaitlist(['rsvp_limit' => 10], 1);

    $event->update(['status' => 'cancelled', 'cancelled_at' => now()]);

    $result = $this->service->promoteNext($event);

    expect($result)->toBeNull();

    // Verify member is still waitlisted
    expect($waitlisted[0]->fresh()->status)->toBe(RsvpStatus::Waitlisted);
});

// Notification sent

it('sends PromotedFromWaitlist notification to the promoted member', function (): void {
    Notification::fake();

    [$event, $waitlisted] = createEventWithWaitlist(['rsvp_limit' => 5], 1);
    $user = $waitlisted[0]->user;

    $this->service->promoteNext($event);

    Notification::assertSentTo($user, PromotedFromWaitlist::class, function ($notification) use ($event): bool {
        return $notification->event->id === $event->id;
    });
});

it('does not send notification when no one is promoted', function (): void {
    Notification::fake();

    $group = Group::factory()->create();
    $event = Event::factory()->published()->create([
        'group_id' => $group->id,
        'starts_at' => now()->addDays(7),
        'rsvp_limit' => 10,
    ]);

    $this->service->promoteNext($event);

    Notification::assertNothingSent();
});

// No promotion when no available spots

it('returns null when no spots are available', function (): void {
    $group = Group::factory()->create();
    $event = Event::factory()->published()->create([
        'group_id' => $group->id,
        'starts_at' => now()->addDays(7),
        'rsvp_limit' => 1,
    ]);

    // Fill the only spot
    $goingUser = User::factory()->create();
    $group->members()->attach($goingUser, ['role' => 'member', 'joined_at' => now()]);
    Rsvp::create([
        'event_id' => $event->id,
        'user_id' => $goingUser->id,
        'status' => RsvpStatus::Going,
        'guest_count' => 0,
    ]);

    // Add someone to waitlist
    $waitUser = User::factory()->create();
    $group->members()->attach($waitUser, ['role' => 'member', 'joined_at' => now()]);
    Rsvp::create([
        'event_id' => $event->id,
        'user_id' => $waitUser->id,
        'status' => RsvpStatus::Waitlisted,
        'guest_count' => 0,
        'waitlisted_at' => now(),
    ]);

    $result = $this->service->promoteNext($event);

    expect($result)->toBeNull();
});

// Unlimited capacity event

it('promotes from waitlist when event has no rsvp limit', function (): void {
    Notification::fake();

    [$event, $waitlisted] = createEventWithWaitlist(['rsvp_limit' => null], 2);

    $promoted = $this->service->promoteNext($event);

    expect($promoted)->not->toBeNull()
        ->and($promoted->id)->toBe($waitlisted[0]->id)
        ->and($promoted->status)->toBe(RsvpStatus::Going);
});
