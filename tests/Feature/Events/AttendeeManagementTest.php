<?php

use App\Enums\AttendanceResult;
use App\Enums\EventStatus;
use App\Enums\RsvpStatus;
use App\Livewire\AttendeeManager;
use App\Models\Event;
use App\Models\Group;
use App\Models\Rsvp;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);
});

function createOrganizerWithEvent(array $eventOverrides = []): array
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

// --- Page Access ---

it('allows event organizer to view attendee management page', function (): void {
    [$organizer, $group, $event] = createOrganizerWithEvent();

    $this->actingAs($organizer)
        ->get(route('events.attendees', [$group, $event]))
        ->assertOk()
        ->assertSee('Attendees');
});

it('allows event host to view attendee management page', function (): void {
    $host = User::factory()->create(['email_verified_at' => now()]);
    $group = Group::factory()->create();
    $group->members()->attach($host->id, ['role' => 'member', 'joined_at' => now()]);

    $event = Event::factory()->published()->create([
        'group_id' => $group->id,
        'created_by' => $host->id,
    ]);
    $event->hosts()->attach($host->id);

    $this->actingAs($host)
        ->get(route('events.attendees', [$group, $event]))
        ->assertOk();
});

it('denies regular member access to attendee management page', function (): void {
    $member = User::factory()->create(['email_verified_at' => now()]);
    $group = Group::factory()->create();
    $group->members()->attach($member->id, ['role' => 'member', 'joined_at' => now()]);

    $event = Event::factory()->published()->create([
        'group_id' => $group->id,
        'created_by' => User::factory(),
    ]);

    $this->actingAs($member)
        ->get(route('events.attendees', [$group, $event]))
        ->assertForbidden();
});

// --- Tabs ---

it('displays going attendees by default', function (): void {
    [$organizer, $group, $event] = createOrganizerWithEvent();

    $goingUser = User::factory()->create();
    Rsvp::factory()->going()->create(['event_id' => $event->id, 'user_id' => $goingUser->id]);

    $waitlistedUser = User::factory()->create();
    Rsvp::factory()->waitlisted()->create(['event_id' => $event->id, 'user_id' => $waitlistedUser->id]);

    Livewire::actingAs($organizer)
        ->test(AttendeeManager::class, ['event' => $event])
        ->assertSee($goingUser->name)
        ->assertDontSee($waitlistedUser->name);
});

it('switches to waitlisted tab', function (): void {
    [$organizer, $group, $event] = createOrganizerWithEvent();

    $waitlistedUser = User::factory()->create();
    Rsvp::factory()->waitlisted()->create(['event_id' => $event->id, 'user_id' => $waitlistedUser->id]);

    $goingUser = User::factory()->create();
    Rsvp::factory()->going()->create(['event_id' => $event->id, 'user_id' => $goingUser->id]);

    Livewire::actingAs($organizer)
        ->test(AttendeeManager::class, ['event' => $event])
        ->call('setTab', 'waitlisted')
        ->assertSee($waitlistedUser->name)
        ->assertDontSee($goingUser->name);
});

it('switches to not going tab', function (): void {
    [$organizer, $group, $event] = createOrganizerWithEvent();

    $notGoingUser = User::factory()->create();
    Rsvp::factory()->notGoing()->create(['event_id' => $event->id, 'user_id' => $notGoingUser->id]);

    Livewire::actingAs($organizer)
        ->test(AttendeeManager::class, ['event' => $event])
        ->call('setTab', 'not_going')
        ->assertSee($notGoingUser->name);
});

// --- Change RSVP Status ---

it('allows organizer to change RSVP status', function (): void {
    [$organizer, $group, $event] = createOrganizerWithEvent();

    $rsvp = Rsvp::factory()->going()->create(['event_id' => $event->id, 'user_id' => User::factory()]);

    Livewire::actingAs($organizer)
        ->test(AttendeeManager::class, ['event' => $event])
        ->call('changeStatus', $rsvp->id, 'waitlisted');

    expect($rsvp->fresh()->status)->toBe(RsvpStatus::Waitlisted);
});

it('allows organizer to change status to not going', function (): void {
    [$organizer, $group, $event] = createOrganizerWithEvent();

    $rsvp = Rsvp::factory()->going()->create(['event_id' => $event->id, 'user_id' => User::factory()]);

    Livewire::actingAs($organizer)
        ->test(AttendeeManager::class, ['event' => $event])
        ->call('changeStatus', $rsvp->id, 'not_going');

    expect($rsvp->fresh()->status)->toBe(RsvpStatus::NotGoing);
});

// --- Move Waitlisted to Going ---

it('allows organizer to move waitlisted to going', function (): void {
    [$organizer, $group, $event] = createOrganizerWithEvent();

    $rsvp = Rsvp::factory()->waitlisted()->create(['event_id' => $event->id, 'user_id' => User::factory()]);

    Livewire::actingAs($organizer)
        ->test(AttendeeManager::class, ['event' => $event])
        ->call('moveToGoing', $rsvp->id);

    $fresh = $rsvp->fresh();
    expect($fresh->status)->toBe(RsvpStatus::Going)
        ->and($fresh->waitlisted_at)->toBeNull();
});

// --- Remove RSVP ---

it('allows organizer to remove an RSVP', function (): void {
    [$organizer, $group, $event] = createOrganizerWithEvent();

    $rsvp = Rsvp::factory()->going()->create(['event_id' => $event->id, 'user_id' => User::factory()]);

    Livewire::actingAs($organizer)
        ->test(AttendeeManager::class, ['event' => $event])
        ->call('removeRsvp', $rsvp->id);

    expect(Rsvp::find($rsvp->id))->toBeNull();
});

// --- Mark Attendance ---

it('allows organizer to mark attendance as attended', function (): void {
    [$organizer, $group, $event] = createOrganizerWithEvent([
        'starts_at' => now()->subDay(),
        'ends_at' => now()->subHours(22),
        'status' => EventStatus::Past,
    ]);

    $rsvp = Rsvp::factory()->going()->create(['event_id' => $event->id, 'user_id' => User::factory()]);

    Livewire::actingAs($organizer)
        ->test(AttendeeManager::class, ['event' => $event])
        ->call('markAttendance', $rsvp->id, 'attended');

    expect($rsvp->fresh()->attended)->toBe(AttendanceResult::Attended);
});

it('allows organizer to mark attendance as no show', function (): void {
    [$organizer, $group, $event] = createOrganizerWithEvent([
        'starts_at' => now()->subDay(),
        'ends_at' => now()->subHours(22),
        'status' => EventStatus::Past,
    ]);

    $rsvp = Rsvp::factory()->going()->create(['event_id' => $event->id, 'user_id' => User::factory()]);

    Livewire::actingAs($organizer)
        ->test(AttendeeManager::class, ['event' => $event])
        ->call('markAttendance', $rsvp->id, 'no_show');

    expect($rsvp->fresh()->attended)->toBe(AttendanceResult::NoShow);
});

// --- CSV Export ---

it('allows organizer to export attendee CSV', function (): void {
    [$organizer, $group, $event] = createOrganizerWithEvent();

    Rsvp::factory()->going()->withGuests(2)->create([
        'event_id' => $event->id,
        'user_id' => User::factory()->create(['name' => 'Jane Doe']),
    ]);

    $response = $this->actingAs($organizer)
        ->get(route('events.attendees.export', [$group, $event]));

    $response->assertOk()
        ->assertHeader('content-type', 'text/csv; charset=UTF-8')
        ->assertDownload("{$event->slug}-attendees.csv");
});

it('denies regular member from exporting CSV', function (): void {
    $member = User::factory()->create(['email_verified_at' => now()]);
    $group = Group::factory()->create();
    $group->members()->attach($member->id, ['role' => 'member', 'joined_at' => now()]);

    $event = Event::factory()->published()->create([
        'group_id' => $group->id,
        'created_by' => User::factory(),
    ]);

    $this->actingAs($member)
        ->get(route('events.attendees.export', [$group, $event]))
        ->assertForbidden();
});

// --- Authorization ---

it('denies unauthenticated access to attendee page', function (): void {
    $group = Group::factory()->create();
    $event = Event::factory()->published()->create([
        'group_id' => $group->id,
        'created_by' => User::factory(),
    ]);

    $this->get(route('events.attendees', [$group, $event]))
        ->assertRedirect(route('login'));
});

it('denies non-member from changing RSVP status', function (): void {
    $nonMember = User::factory()->create(['email_verified_at' => now()]);
    $group = Group::factory()->create();
    $event = Event::factory()->published()->create([
        'group_id' => $group->id,
        'created_by' => User::factory(),
    ]);

    $rsvp = Rsvp::factory()->going()->create(['event_id' => $event->id, 'user_id' => User::factory()]);

    Livewire::actingAs($nonMember)
        ->test(AttendeeManager::class, ['event' => $event])
        ->call('changeStatus', $rsvp->id, 'waitlisted')
        ->assertForbidden();
});

// --- Pagination ---

it('paginates attendees at 20 per page', function (): void {
    [$organizer, $group, $event] = createOrganizerWithEvent();

    Rsvp::factory()->going()->count(25)->create([
        'event_id' => $event->id,
    ]);

    Livewire::actingAs($organizer)
        ->test(AttendeeManager::class, ['event' => $event])
        ->assertViewHas('rsvps', fn ($rsvps) => $rsvps->count() === 20 && $rsvps->total() === 25);
});
