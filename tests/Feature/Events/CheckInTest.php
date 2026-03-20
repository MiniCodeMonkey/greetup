<?php

use App\Livewire\AttendeeManager;
use App\Models\Event;
use App\Models\Group;
use App\Models\Rsvp;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);
});

function createCheckInSetup(array $eventOverrides = []): array
{
    $organizer = User::factory()->create(['email_verified_at' => now()]);
    $group = Group::factory()->create();
    $group->members()->attach($organizer->id, ['role' => 'event_organizer', 'joined_at' => now()]);

    $event = Event::factory()->published()->create(array_merge([
        'group_id' => $group->id,
        'created_by' => $organizer->id,
    ], $eventOverrides));

    return [$organizer, $group, $event];
}

// --- Check In ---

it('allows organizer to check in an attendee', function (): void {
    [$organizer, $group, $event] = createCheckInSetup();

    $attendeeUser = User::factory()->create();
    $rsvp = Rsvp::factory()->going()->create([
        'event_id' => $event->id,
        'user_id' => $attendeeUser->id,
    ]);

    Livewire::actingAs($organizer)
        ->test(AttendeeManager::class, ['event' => $event])
        ->call('checkIn', $rsvp->id);

    $fresh = $rsvp->fresh();
    expect($fresh->checked_in)->toBeTrue()
        ->and($fresh->checked_in_at)->not->toBeNull()
        ->and($fresh->checked_in_by)->toBe($organizer->id);
});

it('allows event host to check in an attendee', function (): void {
    $host = User::factory()->create(['email_verified_at' => now()]);
    $group = Group::factory()->create();
    $group->members()->attach($host->id, ['role' => 'member', 'joined_at' => now()]);

    $event = Event::factory()->published()->create([
        'group_id' => $group->id,
        'created_by' => $host->id,
    ]);
    $event->hosts()->attach($host->id);

    $attendeeUser = User::factory()->create();
    $rsvp = Rsvp::factory()->going()->create([
        'event_id' => $event->id,
        'user_id' => $attendeeUser->id,
    ]);

    Livewire::actingAs($host)
        ->test(AttendeeManager::class, ['event' => $event])
        ->call('checkIn', $rsvp->id);

    $fresh = $rsvp->fresh();
    expect($fresh->checked_in)->toBeTrue()
        ->and($fresh->checked_in_by)->toBe($host->id);
});

it('denies regular member from checking in attendees', function (): void {
    $member = User::factory()->create(['email_verified_at' => now()]);
    $group = Group::factory()->create();
    $group->members()->attach($member->id, ['role' => 'member', 'joined_at' => now()]);

    $event = Event::factory()->published()->create([
        'group_id' => $group->id,
        'created_by' => User::factory(),
    ]);

    $rsvp = Rsvp::factory()->going()->create([
        'event_id' => $event->id,
        'user_id' => User::factory(),
    ]);

    Livewire::actingAs($member)
        ->test(AttendeeManager::class, ['event' => $event])
        ->call('checkIn', $rsvp->id)
        ->assertForbidden();
});

it('sets checked_in_at to current time', function (): void {
    [$organizer, $group, $event] = createCheckInSetup();

    $rsvp = Rsvp::factory()->going()->create([
        'event_id' => $event->id,
        'user_id' => User::factory(),
    ]);

    Livewire::actingAs($organizer)
        ->test(AttendeeManager::class, ['event' => $event])
        ->call('checkIn', $rsvp->id);

    $fresh = $rsvp->fresh();
    expect($fresh->checked_in_at)->not->toBeNull()
        ->and($fresh->checked_in_at->diffInSeconds(now()))->toBeLessThan(5);
});

it('sets checked_in_by to current user', function (): void {
    [$organizer, $group, $event] = createCheckInSetup();

    $rsvp = Rsvp::factory()->going()->create([
        'event_id' => $event->id,
        'user_id' => User::factory(),
    ]);

    Livewire::actingAs($organizer)
        ->test(AttendeeManager::class, ['event' => $event])
        ->call('checkIn', $rsvp->id);

    expect($rsvp->fresh()->checked_in_by)->toBe($organizer->id);
});

it('prevents checking in RSVP from different event', function (): void {
    [$organizer, $group, $event] = createCheckInSetup();

    $otherEvent = Event::factory()->published()->create([
        'group_id' => $group->id,
        'created_by' => $organizer->id,
    ]);

    $rsvp = Rsvp::factory()->going()->create([
        'event_id' => $otherEvent->id,
        'user_id' => User::factory(),
    ]);

    $this->expectException(ModelNotFoundException::class);

    Livewire::actingAs($organizer)
        ->test(AttendeeManager::class, ['event' => $event])
        ->call('checkIn', $rsvp->id);
});

it('denies suspended user from checking in', function (): void {
    $organizer = User::factory()->create([
        'email_verified_at' => now(),
        'is_suspended' => true,
    ]);
    $group = Group::factory()->create();
    $group->members()->attach($organizer->id, ['role' => 'event_organizer', 'joined_at' => now()]);

    $event = Event::factory()->published()->create([
        'group_id' => $group->id,
        'created_by' => $organizer->id,
    ]);

    $rsvp = Rsvp::factory()->going()->create([
        'event_id' => $event->id,
        'user_id' => User::factory(),
    ]);

    Livewire::actingAs($organizer)
        ->test(AttendeeManager::class, ['event' => $event])
        ->call('checkIn', $rsvp->id)
        ->assertForbidden();
});
