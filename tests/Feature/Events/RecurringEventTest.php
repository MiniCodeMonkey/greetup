<?php

use App\Enums\EventStatus;
use App\Enums\GroupRole;
use App\Models\Event;
use App\Models\EventSeries;
use App\Models\Group;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);
    $this->withoutVite();
});

function createGroupWithOrganizer(GroupRole $role = GroupRole::EventOrganizer): array
{
    $organizer = User::factory()->create();
    $group = Group::factory()->create(['organizer_id' => $organizer->id, 'timezone' => 'America/New_York']);
    $user = User::factory()->create();
    $group->members()->attach($user->id, ['role' => $role->value, 'joined_at' => now()]);

    return [$user, $group, $organizer];
}

// --- Series Creation ---

it('creates a recurring event series with weekly pattern', function (): void {
    [$user, $group] = createGroupWithOrganizer();

    $startsAt = now()->addDays(3)->setTime(18, 0);

    $this->actingAs($user)
        ->post(route('events.store', $group), [
            'name' => 'Weekly Standup',
            'event_type' => 'in_person',
            'starts_at' => $startsAt->format('Y-m-d\TH:i'),
            'ends_at' => $startsAt->copy()->addHours(2)->format('Y-m-d\TH:i'),
            'venue_name' => 'Office',
            'venue_address' => '123 Main St',
            'is_recurring' => '1',
            'recurrence_pattern' => 'weekly',
            'status' => 'published',
            'is_chat_enabled' => '1',
            'is_comments_enabled' => '1',
        ])
        ->assertRedirect(route('groups.show', $group))
        ->assertSessionHas('status');

    $series = EventSeries::where('group_id', $group->id)->first();
    expect($series)->not->toBeNull();
    expect($series->recurrence_rule)->toContain('FREQ=WEEKLY');

    $events = Event::where('series_id', $series->id)->get();
    expect($events->count())->toBeGreaterThanOrEqual(4);

    foreach ($events as $event) {
        expect($event->name)->toBe('Weekly Standup');
        expect($event->group_id)->toBe($group->id);
        expect($event->series_id)->toBe($series->id);
    }
});

it('creates a recurring event series with biweekly pattern', function (): void {
    [$user, $group] = createGroupWithOrganizer();

    $startsAt = now()->addDays(3)->setTime(18, 0);

    $this->actingAs($user)
        ->post(route('events.store', $group), [
            'name' => 'Biweekly Review',
            'event_type' => 'online',
            'starts_at' => $startsAt->format('Y-m-d\TH:i'),
            'online_link' => 'https://zoom.us/j/123',
            'is_recurring' => '1',
            'recurrence_pattern' => 'biweekly',
            'status' => 'published',
            'is_chat_enabled' => '1',
            'is_comments_enabled' => '1',
        ])
        ->assertRedirect(route('groups.show', $group));

    $series = EventSeries::where('group_id', $group->id)->first();
    expect($series)->not->toBeNull();
    expect($series->recurrence_rule)->toContain('INTERVAL=2');

    $events = Event::where('series_id', $series->id)->get();
    expect($events->count())->toBeGreaterThanOrEqual(2);
});

it('creates a recurring event series with monthly pattern', function (): void {
    [$user, $group] = createGroupWithOrganizer();

    $startsAt = now()->addDays(3)->setTime(18, 0);

    $this->actingAs($user)
        ->post(route('events.store', $group), [
            'name' => 'Monthly Meetup',
            'event_type' => 'in_person',
            'starts_at' => $startsAt->format('Y-m-d\TH:i'),
            'venue_name' => 'Conference Center',
            'venue_address' => '456 Oak Ave',
            'is_recurring' => '1',
            'recurrence_pattern' => 'monthly',
            'status' => 'published',
            'is_chat_enabled' => '1',
            'is_comments_enabled' => '1',
        ])
        ->assertRedirect(route('groups.show', $group));

    $series = EventSeries::where('group_id', $group->id)->first();
    expect($series)->not->toBeNull();
    expect($series->recurrence_rule)->toContain('FREQ=MONTHLY');

    $events = Event::where('series_id', $series->id)->get();
    expect($events->count())->toBeGreaterThanOrEqual(2);
});

it('creates a recurring event series with custom RRULE', function (): void {
    [$user, $group] = createGroupWithOrganizer();

    $startsAt = now()->addDays(3)->setTime(18, 0);

    $this->actingAs($user)
        ->post(route('events.store', $group), [
            'name' => 'Custom Recurring',
            'event_type' => 'in_person',
            'starts_at' => $startsAt->format('Y-m-d\TH:i'),
            'venue_name' => 'Lab',
            'venue_address' => '789 Elm St',
            'is_recurring' => '1',
            'recurrence_pattern' => 'custom',
            'custom_rrule' => 'DTSTART='.$startsAt->utc()->format('Ymd\THis\Z').';FREQ=WEEKLY;INTERVAL=3;BYDAY=FR',
            'status' => 'published',
            'is_chat_enabled' => '1',
            'is_comments_enabled' => '1',
        ])
        ->assertRedirect(route('groups.show', $group));

    $series = EventSeries::where('group_id', $group->id)->first();
    expect($series)->not->toBeNull();
    expect($series->recurrence_rule)->toContain('FREQ=WEEKLY');
    expect($series->recurrence_rule)->toContain('INTERVAL=3');
});

it('generates correct number of instances for 3 months ahead', function (): void {
    [$user, $group] = createGroupWithOrganizer();

    $startsAt = now()->addDay()->setTime(10, 0);

    $this->actingAs($user)
        ->post(route('events.store', $group), [
            'name' => 'Weekly Check-in',
            'event_type' => 'online',
            'starts_at' => $startsAt->format('Y-m-d\TH:i'),
            'online_link' => 'https://meet.example.com/abc',
            'is_recurring' => '1',
            'recurrence_pattern' => 'weekly',
            'status' => 'published',
            'is_chat_enabled' => '1',
            'is_comments_enabled' => '1',
        ]);

    $series = EventSeries::where('group_id', $group->id)->first();
    $events = Event::where('series_id', $series->id)->orderBy('starts_at')->get();

    // Weekly over 3 months = approximately 12-14 events
    expect($events->count())->toBeGreaterThanOrEqual(12)
        ->and($events->count())->toBeLessThanOrEqual(14);

    // All events should be within 3 months
    $threeMonthsFromNow = now()->addMonths(3);
    foreach ($events as $event) {
        expect($event->starts_at->lessThanOrEqualTo($threeMonthsFromNow))->toBeTrue();
    }
});

it('attaches the creator as host to all series instances', function (): void {
    [$user, $group] = createGroupWithOrganizer();

    $startsAt = now()->addDays(3)->setTime(18, 0);

    $this->actingAs($user)
        ->post(route('events.store', $group), [
            'name' => 'Hosted Series',
            'event_type' => 'online',
            'starts_at' => $startsAt->format('Y-m-d\TH:i'),
            'online_link' => 'https://zoom.us/j/999',
            'is_recurring' => '1',
            'recurrence_pattern' => 'weekly',
            'status' => 'published',
            'is_chat_enabled' => '1',
            'is_comments_enabled' => '1',
        ]);

    $series = EventSeries::where('group_id', $group->id)->first();
    $events = Event::where('series_id', $series->id)->get();

    foreach ($events as $event) {
        expect($event->hosts()->where('user_id', $user->id)->exists())->toBeTrue();
    }
});

// --- Edit single event in series ---

it('edits a single event in a series', function (): void {
    [$user, $group] = createGroupWithOrganizer();

    $series = EventSeries::factory()->create(['group_id' => $group->id]);
    $events = Event::factory()
        ->count(3)
        ->published()
        ->sequence(
            ['starts_at' => now()->addWeek(), 'name' => 'Original Name'],
            ['starts_at' => now()->addWeeks(2), 'name' => 'Original Name'],
            ['starts_at' => now()->addWeeks(3), 'name' => 'Original Name'],
        )
        ->create([
            'group_id' => $group->id,
            'series_id' => $series->id,
            'created_by' => $user->id,
        ]);

    $targetEvent = $events[0];

    $this->actingAs($user)
        ->put(route('events.update', [$group, $targetEvent]), [
            'name' => 'Updated Single Event',
            'starts_at' => $targetEvent->starts_at->format('Y-m-d H:i'),
            'event_type' => $targetEvent->event_type->value,
            'venue_name' => $targetEvent->venue_name,
            'venue_address' => $targetEvent->venue_address,
            'edit_scope' => 'single',
        ])
        ->assertRedirect(route('groups.show', $group))
        ->assertSessionHas('status', 'Event updated successfully.');

    expect($targetEvent->fresh()->name)->toBe('Updated Single Event');
    expect($events[1]->fresh()->name)->toBe('Original Name');
    expect($events[2]->fresh()->name)->toBe('Original Name');
});

// --- Edit all future events in series ---

it('edits all future events in a series', function (): void {
    [$user, $group] = createGroupWithOrganizer();

    $series = EventSeries::factory()->create(['group_id' => $group->id]);
    $events = Event::factory()
        ->count(4)
        ->published()
        ->sequence(
            ['starts_at' => now()->subWeek(), 'name' => 'Past Event'],
            ['starts_at' => now()->addWeek(), 'name' => 'Original Name'],
            ['starts_at' => now()->addWeeks(2), 'name' => 'Original Name'],
            ['starts_at' => now()->addWeeks(3), 'name' => 'Original Name'],
        )
        ->create([
            'group_id' => $group->id,
            'series_id' => $series->id,
            'created_by' => $user->id,
        ]);

    $targetEvent = $events[1]; // Start from the second event (first future)

    $this->actingAs($user)
        ->put(route('events.update', [$group, $targetEvent]), [
            'name' => 'Updated Future Events',
            'starts_at' => $targetEvent->starts_at->format('Y-m-d H:i'),
            'event_type' => $targetEvent->event_type->value,
            'venue_name' => $targetEvent->venue_name,
            'venue_address' => $targetEvent->venue_address,
            'edit_scope' => 'all_future',
        ])
        ->assertRedirect(route('groups.show', $group))
        ->assertSessionHas('status', 'This and all future events updated.');

    // Past event should NOT be updated
    expect($events[0]->fresh()->name)->toBe('Past Event');
    // Target and future events should be updated
    expect($events[1]->fresh()->name)->toBe('Updated Future Events');
    expect($events[2]->fresh()->name)->toBe('Updated Future Events');
    expect($events[3]->fresh()->name)->toBe('Updated Future Events');
});

// --- Cancel single event in series ---

it('cancels a single event in a series', function (): void {
    [$user, $group] = createGroupWithOrganizer();

    $series = EventSeries::factory()->create(['group_id' => $group->id]);
    $events = Event::factory()
        ->count(3)
        ->published()
        ->sequence(
            ['starts_at' => now()->addWeek()],
            ['starts_at' => now()->addWeeks(2)],
            ['starts_at' => now()->addWeeks(3)],
        )
        ->create([
            'group_id' => $group->id,
            'series_id' => $series->id,
            'created_by' => $user->id,
        ]);

    $targetEvent = $events[0];

    $this->actingAs($user)
        ->post(route('events.cancel', [$group, $targetEvent]), [
            'cancel_scope' => 'single',
            'cancellation_reason' => 'Weather',
        ])
        ->assertRedirect(route('groups.show', $group))
        ->assertSessionHas('status', 'Event cancelled.');

    expect($targetEvent->fresh()->status)->toBe(EventStatus::Cancelled);
    expect($targetEvent->fresh()->cancelled_at)->not->toBeNull();
    expect($targetEvent->fresh()->cancellation_reason)->toBe('Weather');

    // Other events remain published
    expect($events[1]->fresh()->status)->toBe(EventStatus::Published);
    expect($events[2]->fresh()->status)->toBe(EventStatus::Published);
});

// --- Cancel all future events in series ---

it('cancels all future events in a series', function (): void {
    [$user, $group] = createGroupWithOrganizer();

    $series = EventSeries::factory()->create(['group_id' => $group->id]);
    $events = Event::factory()
        ->count(4)
        ->published()
        ->sequence(
            ['starts_at' => now()->subWeek()],
            ['starts_at' => now()->addWeek()],
            ['starts_at' => now()->addWeeks(2)],
            ['starts_at' => now()->addWeeks(3)],
        )
        ->create([
            'group_id' => $group->id,
            'series_id' => $series->id,
            'created_by' => $user->id,
        ]);

    $targetEvent = $events[1]; // Start cancelling from first future event

    $this->actingAs($user)
        ->post(route('events.cancel', [$group, $targetEvent]), [
            'cancel_scope' => 'all_future',
            'cancellation_reason' => 'Series discontinued',
        ])
        ->assertRedirect(route('groups.show', $group))
        ->assertSessionHas('status', '3 events cancelled.');

    // Past event is untouched (already published, not in future scope)
    expect($events[0]->fresh()->status)->toBe(EventStatus::Published);

    // All future events are cancelled
    expect($events[1]->fresh()->status)->toBe(EventStatus::Cancelled);
    expect($events[2]->fresh()->status)->toBe(EventStatus::Cancelled);
    expect($events[3]->fresh()->status)->toBe(EventStatus::Cancelled);
});

// --- Form display ---

it('displays the recurring event checkbox on the creation form', function (): void {
    [$user, $group] = createGroupWithOrganizer();

    $this->actingAs($user)
        ->get(route('events.create', $group))
        ->assertStatus(200)
        ->assertSee('Make this recurring')
        ->assertSee('recurrence_pattern');
});

it('displays edit scope prompt for series events on edit form', function (): void {
    [$user, $group] = createGroupWithOrganizer();

    $series = EventSeries::factory()->create(['group_id' => $group->id]);
    $event = Event::factory()->published()->create([
        'group_id' => $group->id,
        'series_id' => $series->id,
        'created_by' => $user->id,
        'starts_at' => now()->addWeek(),
    ]);

    $this->actingAs($user)
        ->get(route('events.edit', [$group, $event]))
        ->assertStatus(200)
        ->assertSee('Edit this event only')
        ->assertSee('Edit this and all future events')
        ->assertSee('Cancel this event only')
        ->assertSee('Cancel this and all future events');
});

it('does not display series prompts for non-series events on edit form', function (): void {
    [$user, $group] = createGroupWithOrganizer();

    $event = Event::factory()->published()->create([
        'group_id' => $group->id,
        'created_by' => $user->id,
        'starts_at' => now()->addWeek(),
    ]);

    $this->actingAs($user)
        ->get(route('events.edit', [$group, $event]))
        ->assertStatus(200)
        ->assertDontSee('Edit this event only')
        ->assertDontSee('Cancel this and all future events');
});

// --- Validation ---

it('requires recurrence_pattern when is_recurring is checked', function (): void {
    [$user, $group] = createGroupWithOrganizer();

    $startsAt = now()->addDays(3)->setTime(18, 0);

    $this->actingAs($user)
        ->post(route('events.store', $group), [
            'name' => 'Test Event',
            'event_type' => 'in_person',
            'starts_at' => $startsAt->format('Y-m-d\TH:i'),
            'venue_name' => 'Office',
            'venue_address' => '123 Main St',
            'is_recurring' => '1',
            'status' => 'published',
            'is_chat_enabled' => '1',
            'is_comments_enabled' => '1',
        ])
        ->assertSessionHasErrors('recurrence_pattern');
});

it('requires custom_rrule when recurrence_pattern is custom', function (): void {
    [$user, $group] = createGroupWithOrganizer();

    $startsAt = now()->addDays(3)->setTime(18, 0);

    $this->actingAs($user)
        ->post(route('events.store', $group), [
            'name' => 'Test Event',
            'event_type' => 'in_person',
            'starts_at' => $startsAt->format('Y-m-d\TH:i'),
            'venue_name' => 'Office',
            'venue_address' => '123 Main St',
            'is_recurring' => '1',
            'recurrence_pattern' => 'custom',
            'status' => 'published',
            'is_chat_enabled' => '1',
            'is_comments_enabled' => '1',
        ])
        ->assertSessionHasErrors('custom_rrule');
});

// --- Non-recurring still works ---

it('still creates a non-recurring event when is_recurring is false', function (): void {
    [$user, $group] = createGroupWithOrganizer();

    $startsAt = now()->addDays(3)->setTime(18, 0);

    $this->actingAs($user)
        ->post(route('events.store', $group), [
            'name' => 'One-off Event',
            'event_type' => 'in_person',
            'starts_at' => $startsAt->format('Y-m-d\TH:i'),
            'venue_name' => 'Office',
            'venue_address' => '123 Main St',
            'is_recurring' => '0',
            'status' => 'published',
            'is_chat_enabled' => '1',
            'is_comments_enabled' => '1',
        ])
        ->assertRedirect(route('groups.show', $group));

    expect(EventSeries::count())->toBe(0);
    expect(Event::where('name', 'One-off Event')->first()->series_id)->toBeNull();
});
