<?php

use App\Enums\RsvpStatus;
use App\Jobs\PromoteFromWaitlist;
use App\Models\Event;
use App\Models\Group;
use App\Models\Rsvp;
use App\Models\User;
use App\Notifications\PromotedFromWaitlist;
use App\Services\RsvpService;
use App\Services\WaitlistService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);
    Notification::fake();
});

function createWaitlistScenario(int $rsvpLimit, int $goingCount = 0, array $waitlistedMembers = []): array
{
    $group = Group::factory()->create();
    $event = Event::factory()->published()->create([
        'group_id' => $group->id,
        'starts_at' => now()->addDays(7),
        'rsvp_limit' => $rsvpLimit,
    ]);

    // Create going RSVPs to fill spots
    $goingUsers = [];
    for ($i = 0; $i < $goingCount; $i++) {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $group->members()->attach($user->id, ['role' => 'member', 'joined_at' => now()]);
        Rsvp::create([
            'event_id' => $event->id,
            'user_id' => $user->id,
            'status' => RsvpStatus::Going,
            'guest_count' => 0,
        ]);
        $goingUsers[] = $user;
    }

    // Create waitlisted RSVPs
    $waitlisted = [];
    foreach ($waitlistedMembers as $index => $guestCount) {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $group->members()->attach($user->id, ['role' => 'member', 'joined_at' => now()]);
        $rsvp = Rsvp::create([
            'event_id' => $event->id,
            'user_id' => $user->id,
            'status' => RsvpStatus::Waitlisted,
            'guest_count' => $guestCount,
            'waitlisted_at' => now()->addSeconds($index),
        ]);
        $waitlisted[] = $rsvp;
    }

    return [$event, $group, $goingUsers, $waitlisted];
}

// --- FIFO Ordering ---

it('promotes waitlisted members in FIFO order by waitlisted_at', function (): void {
    // 3 spots, 0 going, 3 waitlisted (all solo) — should promote in order
    [$event, $group, $goingUsers, $waitlisted] = createWaitlistScenario(
        rsvpLimit: 3,
        goingCount: 0,
        waitlistedMembers: [0, 0, 0],
    );

    $service = new WaitlistService;
    $promoted = $service->promoteAll($event);

    expect($promoted)->toHaveCount(3)
        ->and($promoted[0]->id)->toBe($waitlisted[0]->id)
        ->and($promoted[1]->id)->toBe($waitlisted[1]->id)
        ->and($promoted[2]->id)->toBe($waitlisted[2]->id);

    // All should now be Going
    foreach ($waitlisted as $rsvp) {
        expect($rsvp->fresh()->status)->toBe(RsvpStatus::Going);
    }
});

// --- Guest Skipping with Next-Eligible Promotion ---

it('skips waitlisted members whose guest count exceeds available spots and promotes next eligible', function (): void {
    // 3 spots total, 1 going (1 spot taken), 2 available
    // Waitlisted: first needs 3 spots (1+2 guests) — skip, second needs 1 spot — promote
    [$event, $group, $goingUsers, $waitlisted] = createWaitlistScenario(
        rsvpLimit: 3,
        goingCount: 1,
        waitlistedMembers: [2, 0],
    );

    $service = new WaitlistService;
    $promoted = $service->promoteAll($event);

    // Only the solo member should be promoted
    expect($promoted)->toHaveCount(1)
        ->and($promoted[0]->id)->toBe($waitlisted[1]->id);

    // Big party still waitlisted
    expect($waitlisted[0]->fresh()->status)->toBe(RsvpStatus::Waitlisted);
    // Solo member promoted
    expect($waitlisted[1]->fresh()->status)->toBe(RsvpStatus::Going);
});

// --- Multi-Spot Opening Revisits Skipped Members ---

it('revisits previously skipped members when enough spots become available', function (): void {
    // 5 spots total, 0 going
    // Waitlisted: A needs 3 spots (1+2), B needs 1, C needs 1
    // Pass 1: A promoted (3 spots used, 2 remain)
    // Pass 2: B promoted (1 spot used, 1 remains)
    // Pass 3: C promoted (1 spot used, 0 remain)
    [$event, $group, $goingUsers, $waitlisted] = createWaitlistScenario(
        rsvpLimit: 5,
        goingCount: 0,
        waitlistedMembers: [2, 0, 0],
    );

    $service = new WaitlistService;
    $promoted = $service->promoteAll($event);

    expect($promoted)->toHaveCount(3);

    // All promoted to Going
    foreach ($waitlisted as $rsvp) {
        expect($rsvp->fresh()->status)->toBe(RsvpStatus::Going);
    }
});

it('promotes large party first then skips mid-size party and promotes solo on revisit pass', function (): void {
    // 5 spots total, 0 going
    // Waitlisted: A needs 4 (1+3) — promoted first (FIFO), 1 spot remains
    //             B needs 2 (1+1) — skipped, too large for remaining 1 spot
    //             C needs 1 — promoted on revisit pass, 0 spots remain
    //             B revisited — still too large, stays waitlisted
    [$event, $group, $goingUsers, $waitlisted] = createWaitlistScenario(
        rsvpLimit: 5,
        goingCount: 0,
        waitlistedMembers: [3, 1, 0],
    );

    $service = new WaitlistService;
    $promoted = $service->promoteAll($event);

    // A and C promoted, B still waitlisted
    expect($promoted)->toHaveCount(2)
        ->and($promoted[0]->id)->toBe($waitlisted[0]->id)
        ->and($promoted[1]->id)->toBe($waitlisted[2]->id);

    expect($waitlisted[1]->fresh()->status)->toBe(RsvpStatus::Waitlisted);
});

// --- Promotion Notification ---

it('sends PromotedFromWaitlist notification to each promoted member', function (): void {
    [$event, $group, $goingUsers, $waitlisted] = createWaitlistScenario(
        rsvpLimit: 3,
        goingCount: 0,
        waitlistedMembers: [0, 0],
    );

    $service = new WaitlistService;
    $service->promoteAll($event);

    foreach ($waitlisted as $rsvp) {
        Notification::assertSentTo(
            $rsvp->user,
            PromotedFromWaitlist::class,
            function ($notification) use ($event): bool {
                return $notification->event->id === $event->id;
            }
        );
    }
});

// --- No Promotion on Cancelled Event ---

it('does not promote anyone when event is cancelled', function (): void {
    [$event, $group, $goingUsers, $waitlisted] = createWaitlistScenario(
        rsvpLimit: 10,
        goingCount: 0,
        waitlistedMembers: [0, 0],
    );

    $event->update(['status' => 'cancelled', 'cancelled_at' => now()]);

    $service = new WaitlistService;
    $promoted = $service->promoteAll($event->fresh());

    expect($promoted)->toBeEmpty();

    // All still waitlisted
    foreach ($waitlisted as $rsvp) {
        expect($rsvp->fresh()->status)->toBe(RsvpStatus::Waitlisted);
    }

    Notification::assertNothingSent();
});

// --- No Promotion for Empty Waitlist ---

it('does not promote when waitlist is empty', function (): void {
    [$event, $group, $goingUsers, $waitlisted] = createWaitlistScenario(
        rsvpLimit: 10,
        goingCount: 1,
        waitlistedMembers: [],
    );

    $service = new WaitlistService;
    $promoted = $service->promoteAll($event);

    expect($promoted)->toBeEmpty();
    Notification::assertNothingSent();
});

// --- PromoteFromWaitlist Job Integration ---

it('dispatches PromoteFromWaitlist job when a going RSVP is cancelled', function (): void {
    Queue::fake();

    [$event, $group, $goingUsers, $waitlisted] = createWaitlistScenario(
        rsvpLimit: 3,
        goingCount: 0,
        waitlistedMembers: [0],
    );

    // Create a going RSVP and then cancel it
    $user = User::factory()->create(['email_verified_at' => now()]);
    $group->members()->attach($user->id, ['role' => 'member', 'joined_at' => now()]);
    Rsvp::create([
        'event_id' => $event->id,
        'user_id' => $user->id,
        'status' => RsvpStatus::Going,
        'guest_count' => 0,
    ]);

    $rsvpService = app(RsvpService::class);
    $rsvpService->rsvpNotGoing($event, $user);

    Queue::assertPushed(PromoteFromWaitlist::class, function ($job) use ($event): bool {
        return $job->event->id === $event->id;
    });
});

// --- Full End-to-End: Cancellation Triggers Promotion ---

it('promotes waitlisted member when going member cancels RSVP', function (): void {
    $group = Group::factory()->create();
    $event = Event::factory()->published()->create([
        'group_id' => $group->id,
        'starts_at' => now()->addDays(7),
        'rsvp_limit' => 1,
    ]);

    // Going user
    $goingUser = User::factory()->create(['email_verified_at' => now()]);
    $group->members()->attach($goingUser->id, ['role' => 'member', 'joined_at' => now()]);
    Rsvp::create([
        'event_id' => $event->id,
        'user_id' => $goingUser->id,
        'status' => RsvpStatus::Going,
        'guest_count' => 0,
    ]);

    // Waitlisted user
    $waitlistedUser = User::factory()->create(['email_verified_at' => now()]);
    $group->members()->attach($waitlistedUser->id, ['role' => 'member', 'joined_at' => now()]);
    Rsvp::create([
        'event_id' => $event->id,
        'user_id' => $waitlistedUser->id,
        'status' => RsvpStatus::Waitlisted,
        'guest_count' => 0,
        'waitlisted_at' => now(),
    ]);

    // Going user cancels — run the job synchronously
    $rsvpService = app(RsvpService::class);
    $rsvpService->rsvpNotGoing($event, $goingUser);

    // Manually run the job since queue is not being processed
    (new PromoteFromWaitlist($event))->handle(new WaitlistService);

    // Waitlisted user should now be Going
    expect(Rsvp::where('event_id', $event->id)->where('user_id', $waitlistedUser->id)->first())
        ->status->toBe(RsvpStatus::Going)
        ->waitlisted_at->toBeNull();

    Notification::assertSentTo($waitlistedUser, PromotedFromWaitlist::class);
});
