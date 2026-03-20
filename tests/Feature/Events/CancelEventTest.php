<?php

use App\Enums\EventStatus;
use App\Enums\GroupRole;
use App\Enums\RsvpStatus;
use App\Models\Event;
use App\Models\Group;
use App\Models\Rsvp;
use App\Models\User;
use App\Notifications\EventCancelled;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);
    $this->withoutVite();
});

function createCancelTestGroupWithOrganizer(GroupRole $role = GroupRole::EventOrganizer): array
{
    $organizer = User::factory()->create();
    $group = Group::factory()->create(['organizer_id' => $organizer->id, 'timezone' => 'America/New_York']);
    $user = User::factory()->create();
    $group->members()->attach($user->id, ['role' => $role->value, 'joined_at' => now()]);

    return [$user, $group, $organizer];
}

// --- Cancellation ---

it('cancels an event with a reason', function (): void {
    [$user, $group] = createCancelTestGroupWithOrganizer();
    $event = Event::factory()->published()->create([
        'group_id' => $group->id,
        'starts_at' => now()->addDays(3),
    ]);

    Notification::fake();

    $this->actingAs($user)
        ->post(route('events.cancel', [$group, $event]), [
            'cancellation_reason' => 'Venue unavailable',
        ])
        ->assertRedirect(route('groups.show', $group))
        ->assertSessionHas('status', 'Event cancelled.');

    $event->refresh();
    expect($event->status)->toBe(EventStatus::Cancelled);
    expect($event->cancelled_at)->not->toBeNull();
    expect($event->cancellation_reason)->toBe('Venue unavailable');
});

it('cancels an event without a reason', function (): void {
    [$user, $group] = createCancelTestGroupWithOrganizer();
    $event = Event::factory()->published()->create([
        'group_id' => $group->id,
        'starts_at' => now()->addDays(3),
    ]);

    Notification::fake();

    $this->actingAs($user)
        ->post(route('events.cancel', [$group, $event]))
        ->assertRedirect(route('groups.show', $group));

    $event->refresh();
    expect($event->status)->toBe(EventStatus::Cancelled);
    expect($event->cancelled_at)->not->toBeNull();
    expect($event->cancellation_reason)->toBeNull();
});

it('prevents non-event-organizers from cancelling', function (): void {
    [$user, $group] = createCancelTestGroupWithOrganizer(GroupRole::Member);
    $event = Event::factory()->published()->create([
        'group_id' => $group->id,
        'starts_at' => now()->addDays(3),
    ]);

    $this->actingAs($user)
        ->post(route('events.cancel', [$group, $event]))
        ->assertForbidden();

    expect($event->fresh()->status)->toBe(EventStatus::Published);
});

// --- Notifications ---

it('sends EventCancelled notification to going and waitlisted members', function (): void {
    [$user, $group] = createCancelTestGroupWithOrganizer();
    $event = Event::factory()->published()->create([
        'group_id' => $group->id,
        'starts_at' => now()->addDays(3),
    ]);

    $goingUser = User::factory()->create();
    $waitlistedUser = User::factory()->create();
    $notGoingUser = User::factory()->create();

    Rsvp::factory()->create([
        'event_id' => $event->id,
        'user_id' => $goingUser->id,
        'status' => RsvpStatus::Going,
    ]);
    Rsvp::factory()->create([
        'event_id' => $event->id,
        'user_id' => $waitlistedUser->id,
        'status' => RsvpStatus::Waitlisted,
    ]);
    Rsvp::factory()->create([
        'event_id' => $event->id,
        'user_id' => $notGoingUser->id,
        'status' => RsvpStatus::NotGoing,
    ]);

    Notification::fake();

    $this->actingAs($user)
        ->post(route('events.cancel', [$group, $event]), [
            'cancellation_reason' => 'Bad weather',
        ]);

    Notification::assertSentTo($goingUser, EventCancelled::class);
    Notification::assertSentTo($waitlistedUser, EventCancelled::class);
    Notification::assertNotSentTo($notGoingUser, EventCancelled::class);
});

it('retains RSVPs after cancellation', function (): void {
    [$user, $group] = createCancelTestGroupWithOrganizer();
    $event = Event::factory()->published()->create([
        'group_id' => $group->id,
        'starts_at' => now()->addDays(3),
    ]);

    $goingUser = User::factory()->create();
    $rsvp = Rsvp::factory()->create([
        'event_id' => $event->id,
        'user_id' => $goingUser->id,
        'status' => RsvpStatus::Going,
    ]);

    Notification::fake();

    $this->actingAs($user)
        ->post(route('events.cancel', [$group, $event]));

    expect($rsvp->fresh())->not->toBeNull();
    expect($rsvp->fresh()->status)->toBe(RsvpStatus::Going);
});

// --- Cancelled badge on past events list ---

it('shows cancelled badge on past events list', function (): void {
    [$user, $group] = createCancelTestGroupWithOrganizer();

    Event::factory()->create([
        'group_id' => $group->id,
        'name' => 'Cancelled Meetup',
        'status' => EventStatus::Cancelled,
        'starts_at' => now()->subDays(1),
        'cancelled_at' => now()->subDays(1),
        'cancellation_reason' => 'Weather',
    ]);

    Event::factory()->create([
        'group_id' => $group->id,
        'name' => 'Regular Past Meetup',
        'status' => EventStatus::Published,
        'starts_at' => now()->subDays(2),
    ]);

    $this->actingAs($user)
        ->get(route('groups.show', ['group' => $group, 'tab' => 'past']))
        ->assertOk()
        ->assertSee('Cancelled Meetup')
        ->assertSee('Regular Past Meetup')
        ->assertSee('Cancelled</span>', false);
});

it('shows cancelled future events in past events list', function (): void {
    [$user, $group] = createCancelTestGroupWithOrganizer();

    Event::factory()->create([
        'group_id' => $group->id,
        'name' => 'Cancelled Future Event',
        'status' => EventStatus::Cancelled,
        'starts_at' => now()->addDays(5),
        'cancelled_at' => now(),
        'cancellation_reason' => 'Organizer unavailable',
    ]);

    $this->actingAs($user)
        ->get(route('groups.show', ['group' => $group, 'tab' => 'past']))
        ->assertOk()
        ->assertSee('Cancelled Future Event')
        ->assertSee('Cancelled</span>', false);
});
