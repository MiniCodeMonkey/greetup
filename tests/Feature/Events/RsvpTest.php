<?php

use App\Enums\AttendanceMode;
use App\Enums\EventType;
use App\Enums\RsvpStatus;
use App\Jobs\PromoteFromWaitlist;
use App\Livewire\RsvpButton;
use App\Models\Event;
use App\Models\Group;
use App\Models\Rsvp;
use App\Models\User;
use App\Notifications\RsvpConfirmation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);
    Notification::fake();
});

function createMemberWithEvent(array $eventOverrides = [], array $groupOverrides = []): array
{
    $user = User::factory()->create(['email_verified_at' => now()]);
    $group = Group::factory()->create($groupOverrides);
    $group->members()->attach($user->id, ['role' => 'member', 'joined_at' => now()]);

    $event = Event::factory()->published()->create(array_merge([
        'group_id' => $group->id,
        'created_by' => $user->id,
    ], $eventOverrides));

    return [$user, $group, $event];
}

// --- Going ---

it('allows a group member to RSVP as going', function (): void {
    [$user, $group, $event] = createMemberWithEvent();

    Livewire::actingAs($user)
        ->test(RsvpButton::class, ['event' => $event])
        ->call('rsvpGoing')
        ->assertSet('currentStatus', 'going');

    expect(Rsvp::where('event_id', $event->id)->where('user_id', $user->id)->first())
        ->status->toBe(RsvpStatus::Going);
});

it('sends RsvpConfirmation notification when going', function (): void {
    [$user, $group, $event] = createMemberWithEvent();

    Livewire::actingAs($user)
        ->test(RsvpButton::class, ['event' => $event])
        ->call('rsvpGoing');

    Notification::assertSentTo($user, RsvpConfirmation::class);
});

// --- Not Going ---

it('allows a user to change RSVP to not going', function (): void {
    [$user, $group, $event] = createMemberWithEvent();

    Rsvp::factory()->going()->create([
        'event_id' => $event->id,
        'user_id' => $user->id,
    ]);

    Queue::fake();

    Livewire::actingAs($user)
        ->test(RsvpButton::class, ['event' => $event])
        ->call('rsvpNotGoing')
        ->assertSet('currentStatus', 'not_going');

    Queue::assertPushed(PromoteFromWaitlist::class);
});

// --- Waitlist auto-assign ---

it('auto-waitlists when event is full', function (): void {
    [$user, $group, $event] = createMemberWithEvent(['rsvp_limit' => 1]);

    // Fill the event
    $otherUser = User::factory()->create(['email_verified_at' => now()]);
    $group->members()->attach($otherUser->id, ['role' => 'member', 'joined_at' => now()]);
    Rsvp::factory()->going()->create([
        'event_id' => $event->id,
        'user_id' => $otherUser->id,
    ]);

    Livewire::actingAs($user)
        ->test(RsvpButton::class, ['event' => $event])
        ->call('rsvpGoing')
        ->assertSet('currentStatus', 'waitlisted');

    $rsvp = Rsvp::where('event_id', $event->id)->where('user_id', $user->id)->first();
    expect($rsvp->status)->toBe(RsvpStatus::Waitlisted)
        ->and($rsvp->waitlisted_at)->not->toBeNull();
});

it('sends RsvpConfirmation notification when waitlisted', function (): void {
    [$user, $group, $event] = createMemberWithEvent(['rsvp_limit' => 1]);

    $otherUser = User::factory()->create(['email_verified_at' => now()]);
    $group->members()->attach($otherUser->id, ['role' => 'member', 'joined_at' => now()]);
    Rsvp::factory()->going()->create([
        'event_id' => $event->id,
        'user_id' => $otherUser->id,
    ]);

    Livewire::actingAs($user)
        ->test(RsvpButton::class, ['event' => $event])
        ->call('rsvpGoing');

    Notification::assertSentTo($user, RsvpConfirmation::class, function ($notification) {
        return $notification->rsvp->status === RsvpStatus::Waitlisted;
    });
});

// --- Guest count ---

it('allows RSVP with guest count up to event guest limit', function (): void {
    [$user, $group, $event] = createMemberWithEvent(['guest_limit' => 3]);

    Livewire::actingAs($user)
        ->test(RsvpButton::class, ['event' => $event])
        ->set('guestCount', 2)
        ->call('rsvpGoing')
        ->assertSet('currentStatus', 'going');

    expect(Rsvp::where('event_id', $event->id)->where('user_id', $user->id)->first())
        ->guest_count->toBe(2);
});

it('rejects guest count exceeding event guest limit', function (): void {
    [$user, $group, $event] = createMemberWithEvent(['guest_limit' => 2]);

    Livewire::actingAs($user)
        ->test(RsvpButton::class, ['event' => $event])
        ->set('guestCount', 5)
        ->call('rsvpGoing')
        ->assertSet('errorMessage', "Guest count exceeds the event's guest limit of 2.");
});

// --- Hybrid attendance mode ---

it('requires attendance mode for hybrid events', function (): void {
    [$user, $group, $event] = createMemberWithEvent([
        'event_type' => EventType::Hybrid,
        'online_link' => 'https://example.com/meet',
    ]);

    Livewire::actingAs($user)
        ->test(RsvpButton::class, ['event' => $event])
        ->call('rsvpGoing')
        ->assertSet('errorMessage', 'Attendance mode is required for hybrid events.');
});

it('allows RSVP with attendance mode for hybrid events', function (): void {
    [$user, $group, $event] = createMemberWithEvent([
        'event_type' => EventType::Hybrid,
        'online_link' => 'https://example.com/meet',
    ]);

    Livewire::actingAs($user)
        ->test(RsvpButton::class, ['event' => $event])
        ->set('attendanceMode', 'in_person')
        ->call('rsvpGoing')
        ->assertSet('currentStatus', 'going');

    expect(Rsvp::where('event_id', $event->id)->where('user_id', $user->id)->first())
        ->attendance_mode->toBe(AttendanceMode::InPerson);
});

// --- RSVP window enforcement ---

it('rejects RSVP when rsvp_opens_at has not passed', function (): void {
    [$user, $group, $event] = createMemberWithEvent([
        'rsvp_opens_at' => now()->addDay(),
    ]);

    Livewire::actingAs($user)
        ->test(RsvpButton::class, ['event' => $event])
        ->call('rsvpGoing')
        ->assertSet('errorMessage', 'RSVP window has not opened yet.');
});

it('rejects RSVP when rsvp_closes_at has passed', function (): void {
    [$user, $group, $event] = createMemberWithEvent([
        'rsvp_closes_at' => now()->subDay(),
    ]);

    Livewire::actingAs($user)
        ->test(RsvpButton::class, ['event' => $event])
        ->call('rsvpGoing')
        ->assertSet('errorMessage', 'RSVP window has closed.');
});

it('rejects RSVP when event starts_at is past and no ends_at', function (): void {
    [$user, $group, $event] = createMemberWithEvent([
        'starts_at' => now()->subHour(),
        'ends_at' => null,
    ]);

    Livewire::actingAs($user)
        ->test(RsvpButton::class, ['event' => $event])
        ->call('rsvpGoing')
        ->assertSet('errorMessage', 'Cannot RSVP to a past event.');
});

it('rejects RSVP when event ends_at has passed', function (): void {
    [$user, $group, $event] = createMemberWithEvent([
        'starts_at' => now()->subHours(3),
        'ends_at' => now()->subHour(),
    ]);

    Livewire::actingAs($user)
        ->test(RsvpButton::class, ['event' => $event])
        ->call('rsvpGoing')
        ->assertSet('errorMessage', 'Cannot RSVP to a past event.');
});

// --- Non-member rejected ---

it('rejects RSVP from a non-member', function (): void {
    $user = User::factory()->create(['email_verified_at' => now()]);
    $group = Group::factory()->create();
    $event = Event::factory()->published()->create([
        'group_id' => $group->id,
    ]);

    Livewire::actingAs($user)
        ->test(RsvpButton::class, ['event' => $event])
        ->call('rsvpGoing')
        ->assertSet('errorMessage', 'User must be a member of the group to RSVP.');
});

// --- Unverified rejected ---

it('does not allow unverified users to RSVP', function (): void {
    $user = User::factory()->unverified()->create();
    $group = Group::factory()->create();
    $group->members()->attach($user->id, ['role' => 'member', 'joined_at' => now()]);
    $event = Event::factory()->published()->create([
        'group_id' => $group->id,
    ]);

    // The canRsvp check in the component should return false
    Livewire::actingAs($user)
        ->test(RsvpButton::class, ['event' => $event])
        ->assertSet('currentStatus', null)
        ->assertSeeHtml('data-testid="rsvp-button-component"')
        ->assertDontSeeHtml('data-testid="rsvp-going"');
});

// --- Cancelled event rejected ---

it('does not show RSVP button for cancelled events', function (): void {
    [$user, $group, $event] = createMemberWithEvent();
    $event->update(['status' => 'cancelled', 'cancelled_at' => now()]);

    Livewire::actingAs($user)
        ->test(RsvpButton::class, ['event' => $event->fresh()])
        ->assertDontSeeHtml('data-testid="rsvp-going"');
});
