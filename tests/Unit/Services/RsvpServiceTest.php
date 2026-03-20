<?php

use App\Enums\AttendanceMode;
use App\Enums\EventType;
use App\Enums\RsvpStatus;
use App\Jobs\PromoteFromWaitlist;
use App\Models\Event;
use App\Models\Group;
use App\Models\User;
use App\Services\RsvpService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);
    $this->service = new RsvpService;
});

function createPublishedEventWithMember(array $eventAttributes = []): array
{
    $group = Group::factory()->create();
    $user = User::factory()->create();
    $group->members()->attach($user, ['role' => 'member', 'joined_at' => now()]);

    $event = Event::factory()->published()->create(array_merge(
        ['group_id' => $group->id, 'starts_at' => now()->addDays(7)],
        $eventAttributes
    ));

    return [$event, $user, $group];
}

// Going with spots available

it('allows a member to RSVP going when spots are available', function (): void {
    [$event, $user] = createPublishedEventWithMember(['rsvp_limit' => 10]);

    $rsvp = $this->service->rsvpGoing($event, $user);

    expect($rsvp->status)->toBe(RsvpStatus::Going)
        ->and($rsvp->user_id)->toBe($user->id)
        ->and($rsvp->event_id)->toBe($event->id)
        ->and($rsvp->guest_count)->toBe(0);
});

// Auto-waitlist when full

it('auto-waitlists when event is full', function (): void {
    [$event, $user, $group] = createPublishedEventWithMember(['rsvp_limit' => 1]);

    // Fill the event with another member
    $otherUser = User::factory()->create();
    $group->members()->attach($otherUser, ['role' => 'member', 'joined_at' => now()]);
    $this->service->rsvpGoing($event, $otherUser);

    $rsvp = $this->service->rsvpGoing($event, $user);

    expect($rsvp->status)->toBe(RsvpStatus::Waitlisted)
        ->and($rsvp->waitlisted_at)->not->toBeNull();
});

// Cancel triggers waitlist job

it('dispatches PromoteFromWaitlist job when a going RSVP is cancelled', function (): void {
    Queue::fake();

    [$event, $user] = createPublishedEventWithMember(['rsvp_limit' => 10]);

    $this->service->rsvpGoing($event, $user);
    $this->service->rsvpNotGoing($event, $user);

    Queue::assertPushed(PromoteFromWaitlist::class, function (PromoteFromWaitlist $job) use ($event): bool {
        return $job->event->id === $event->id;
    });
});

it('does not dispatch PromoteFromWaitlist when a non-going RSVP is cancelled', function (): void {
    Queue::fake();

    [$event, $user] = createPublishedEventWithMember(['rsvp_limit' => 1]);

    // User is not going, so cancelling should not dispatch
    $this->service->rsvpNotGoing($event, $user);

    Queue::assertNotPushed(PromoteFromWaitlist::class);
});

// Not a member rejected

it('rejects RSVP when user is not a group member', function (): void {
    $group = Group::factory()->create();
    $user = User::factory()->create();

    $event = Event::factory()->published()->create([
        'group_id' => $group->id,
        'starts_at' => now()->addDays(7),
    ]);

    $this->service->rsvpGoing($event, $user);
})->throws(InvalidArgumentException::class, 'User must be a member of the group to RSVP.');

// RSVP window closed rejected

it('rejects RSVP when RSVP window has not opened yet', function (): void {
    [$event, $user] = createPublishedEventWithMember([
        'rsvp_opens_at' => now()->addDays(1),
    ]);

    $this->service->rsvpGoing($event, $user);
})->throws(InvalidArgumentException::class, 'RSVP window has not opened yet.');

it('rejects RSVP when RSVP window has closed', function (): void {
    [$event, $user] = createPublishedEventWithMember([
        'rsvp_closes_at' => now()->subDay(),
    ]);

    $this->service->rsvpGoing($event, $user);
})->throws(InvalidArgumentException::class, 'RSVP window has closed.');

// Past event rejected

it('rejects RSVP to a past event', function (): void {
    [$event, $user] = createPublishedEventWithMember([
        'starts_at' => now()->subDays(2),
        'ends_at' => now()->subDay(),
    ]);

    $this->service->rsvpGoing($event, $user);
})->throws(InvalidArgumentException::class, 'Cannot RSVP to a past event.');

it('rejects RSVP when starts_at is past and no ends_at', function (): void {
    [$event, $user] = createPublishedEventWithMember([
        'starts_at' => now()->subDay(),
        'ends_at' => null,
    ]);

    $this->service->rsvpGoing($event, $user);
})->throws(InvalidArgumentException::class, 'Cannot RSVP to a past event.');

// Cancelled event rejected

it('rejects RSVP to a cancelled event', function (): void {
    $group = Group::factory()->create();
    $user = User::factory()->create();
    $group->members()->attach($user, ['role' => 'member', 'joined_at' => now()]);

    $event = Event::factory()->cancelled()->create([
        'group_id' => $group->id,
        'starts_at' => now()->addDays(7),
    ]);

    $this->service->rsvpGoing($event, $user);
})->throws(InvalidArgumentException::class);

// Guest count exceeds limit rejected

it('rejects RSVP when guest count exceeds event guest limit', function (): void {
    [$event, $user] = createPublishedEventWithMember(['guest_limit' => 2]);

    $this->service->rsvpGoing($event, $user, guestCount: 3);
})->throws(InvalidArgumentException::class, "Guest count exceeds the event's guest limit of 2.");

// Hybrid requires attendance mode

it('rejects RSVP to hybrid event without attendance mode', function (): void {
    [$event, $user] = createPublishedEventWithMember([
        'event_type' => EventType::Hybrid,
        'online_link' => 'https://example.com/meet',
    ]);

    $this->service->rsvpGoing($event, $user);
})->throws(InvalidArgumentException::class, 'Attendance mode is required for hybrid events.');

it('allows RSVP to hybrid event with attendance mode', function (): void {
    [$event, $user] = createPublishedEventWithMember([
        'event_type' => EventType::Hybrid,
        'online_link' => 'https://example.com/meet',
    ]);

    $rsvp = $this->service->rsvpGoing($event, $user, attendanceMode: AttendanceMode::InPerson);

    expect($rsvp->status)->toBe(RsvpStatus::Going)
        ->and($rsvp->attendance_mode)->toBe(AttendanceMode::InPerson);
});

// Join waitlist

it('allows a member to join the waitlist', function (): void {
    [$event, $user] = createPublishedEventWithMember(['rsvp_limit' => 10, 'guest_limit' => 3]);

    $rsvp = $this->service->joinWaitlist($event, $user, guestCount: 1);

    expect($rsvp->status)->toBe(RsvpStatus::Waitlisted)
        ->and($rsvp->waitlisted_at)->not->toBeNull()
        ->and($rsvp->guest_count)->toBe(1);
});

// Guest count + member accounting

it('auto-waitlists when member plus guests exceed available spots', function (): void {
    [$event, $user, $group] = createPublishedEventWithMember([
        'rsvp_limit' => 3,
        'guest_limit' => 5,
    ]);

    // First user takes 1 spot
    $otherUser = User::factory()->create();
    $group->members()->attach($otherUser, ['role' => 'member', 'joined_at' => now()]);
    $this->service->rsvpGoing($event, $otherUser);

    // Second user wants 1 + 2 guests = 3 spots, but only 2 available
    $rsvp = $this->service->rsvpGoing($event, $user, guestCount: 2);

    expect($rsvp->status)->toBe(RsvpStatus::Waitlisted);
});
