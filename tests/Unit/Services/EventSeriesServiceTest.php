<?php

use App\Models\Event;
use App\Models\EventSeries;
use App\Models\Group;
use App\Models\User;
use App\Services\EventSeriesService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);
    $this->service = new EventSeriesService;
});

// --- Create Series ---

it('creates a series and generates recurring instances', function (): void {
    $group = Group::factory()->create();
    $creator = User::factory()->create();

    $rrule = 'FREQ=WEEKLY;BYDAY=MO;DTSTART='.now()->next('Monday')->format('Ymd\THis');

    $series = $this->service->createSeries($group, $rrule, [
        'name' => 'Weekly Meetup',
        'created_by' => $creator->id,
        'event_type' => 'in_person',
        'starts_at' => now()->next('Monday')->setHour(18),
        'ends_at' => now()->next('Monday')->setHour(20),
    ]);

    expect($series)->toBeInstanceOf(EventSeries::class)
        ->and($series->group_id)->toBe($group->id)
        ->and($series->recurrence_rule)->toBe($rrule)
        ->and($series->events()->count())->toBeGreaterThan(0);
});

it('throws on invalid RRULE', function (): void {
    $group = Group::factory()->create();

    $this->service->createSeries($group, 'INVALID_RRULE', [
        'name' => 'Test',
    ]);
})->throws(InvalidArgumentException::class);

// --- Generate Instances ---

it('does not create duplicate instances', function (): void {
    $group = Group::factory()->create();
    $creator = User::factory()->create();

    $rrule = 'FREQ=WEEKLY;BYDAY=MO;DTSTART='.now()->next('Monday')->format('Ymd\THis');

    $series = $this->service->createSeries($group, $rrule, [
        'name' => 'Weekly Meetup',
        'created_by' => $creator->id,
        'event_type' => 'in_person',
    ]);

    $initialCount = $series->events()->count();

    // Generate again - should not duplicate
    $this->service->generateInstances($series, [
        'name' => 'Weekly Meetup',
        'created_by' => $creator->id,
        'event_type' => 'in_person',
    ]);

    expect($series->events()->count())->toBe($initialCount);
});

it('generates events with correct duration', function (): void {
    $group = Group::factory()->create();
    $creator = User::factory()->create();
    $startDate = now()->next('Monday');

    $rrule = 'FREQ=WEEKLY;COUNT=2;BYDAY=MO;DTSTART='.$startDate->format('Ymd\THis');

    $series = $this->service->createSeries($group, $rrule, [
        'name' => 'Weekly Meetup',
        'created_by' => $creator->id,
        'event_type' => 'in_person',
        'starts_at' => $startDate->copy()->setHour(18),
        'ends_at' => $startDate->copy()->setHour(20),
    ]);

    $event = $series->events()->first();

    expect((int) $event->starts_at->diffInMinutes($event->ends_at))->toBe(120);
});

// --- Update Single ---

it('updates a single event in a series', function (): void {
    $group = Group::factory()->create();
    $creator = User::factory()->create();
    $startDate = now()->next('Monday');

    $rrule = 'FREQ=WEEKLY;COUNT=3;BYDAY=MO;DTSTART='.$startDate->format('Ymd\THis');

    $series = $this->service->createSeries($group, $rrule, [
        'name' => 'Weekly Meetup',
        'created_by' => $creator->id,
        'event_type' => 'in_person',
    ]);

    $event = $series->events()->first();

    $updated = $this->service->updateSingle($event, ['name' => 'Special Meetup']);

    expect($updated->name)->toBe('Special Meetup');

    // Other events remain unchanged
    $otherEvents = $series->events()->where('id', '!=', $event->id)->get();

    foreach ($otherEvents as $otherEvent) {
        expect($otherEvent->name)->toBe('Weekly Meetup');
    }
});

it('throws when updating single event not in a series', function (): void {
    $event = Event::factory()->create(['series_id' => null]);

    $this->service->updateSingle($event, ['name' => 'New Name']);
})->throws(InvalidArgumentException::class, 'Event is not part of a series.');

// --- Update All Future ---

it('updates all future events in a series', function (): void {
    $group = Group::factory()->create();
    $creator = User::factory()->create();
    $startDate = now()->next('Monday');

    $rrule = 'FREQ=WEEKLY;COUNT=4;BYDAY=MO;DTSTART='.$startDate->format('Ymd\THis');

    $series = $this->service->createSeries($group, $rrule, [
        'name' => 'Weekly Meetup',
        'created_by' => $creator->id,
        'event_type' => 'in_person',
    ]);

    $events = $series->events()->orderBy('starts_at')->get();
    $secondEvent = $events[1];

    $updated = $this->service->updateAllFuture($secondEvent, ['venue_name' => 'New Venue']);

    // The second and all after should be updated
    expect(count($updated))->toBeGreaterThanOrEqual(3);

    foreach ($updated as $updatedEvent) {
        expect($updatedEvent->venue_name)->toBe('New Venue');
    }
});

it('throws when updating all future for event not in a series', function (): void {
    $event = Event::factory()->create(['series_id' => null]);

    $this->service->updateAllFuture($event, ['name' => 'New Name']);
})->throws(InvalidArgumentException::class, 'Event is not part of a series.');
