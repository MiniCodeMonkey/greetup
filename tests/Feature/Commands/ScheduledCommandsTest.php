<?php

use App\Enums\EventStatus;
use App\Models\Event;
use App\Models\EventSeries;
use App\Models\Group;
use App\Models\User;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);
});

// ── events:mark-past ──────────────────────────────────────────────

it('transitions published events with ends_at in the past to past status', function (): void {
    $event = Event::factory()->published()->create([
        'starts_at' => now()->subHours(5),
        'ends_at' => now()->subHour(),
    ]);

    $this->artisan('events:mark-past')->assertSuccessful();

    expect($event->fresh()->status)->toBe(EventStatus::Past);
});

it('transitions published events without ends_at when starts_at + 3 hours has passed', function (): void {
    $event = Event::factory()->published()->create([
        'starts_at' => now()->subHours(4),
        'ends_at' => null,
    ]);

    $this->artisan('events:mark-past')->assertSuccessful();

    expect($event->fresh()->status)->toBe(EventStatus::Past);
});

it('does not transition published events without ends_at when starts_at + 3 hours has not passed', function (): void {
    $event = Event::factory()->published()->create([
        'starts_at' => now()->subHours(2),
        'ends_at' => null,
    ]);

    $this->artisan('events:mark-past')->assertSuccessful();

    expect($event->fresh()->status)->toBe(EventStatus::Published);
});

it('does not transition events that are not published', function (): void {
    $event = Event::factory()->draft()->create([
        'starts_at' => now()->subHours(5),
        'ends_at' => now()->subHour(),
    ]);

    $this->artisan('events:mark-past')->assertSuccessful();

    expect($event->fresh()->status)->toBe(EventStatus::Draft);
});

it('does not transition published events with ends_at in the future', function (): void {
    $event = Event::factory()->published()->create([
        'starts_at' => now()->subHour(),
        'ends_at' => now()->addHour(),
    ]);

    $this->artisan('events:mark-past')->assertSuccessful();

    expect($event->fresh()->status)->toBe(EventStatus::Published);
});

// ── accounts:purge-deleted ────────────────────────────────────────

it('purges users soft-deleted more than 30 days ago', function (): void {
    $user = User::factory()->create();
    $user->delete();
    User::withTrashed()->where('id', $user->id)->toBase()->update(['deleted_at' => now()->subDays(31)]);

    $this->artisan('accounts:purge-deleted')->assertSuccessful();

    expect(User::withTrashed()->find($user->id))->toBeNull();
});

it('does not purge users soft-deleted less than 30 days ago', function (): void {
    $user = User::factory()->create();
    $user->delete();
    User::withTrashed()->where('id', $user->id)->toBase()->update(['deleted_at' => now()->subDays(29)]);

    $this->artisan('accounts:purge-deleted')->assertSuccessful();

    expect(User::withTrashed()->find($user->id))->not->toBeNull();
});

it('does not purge active users', function (): void {
    $user = User::factory()->create();

    $this->artisan('accounts:purge-deleted')->assertSuccessful();

    expect(User::find($user->id))->not->toBeNull();
});

// ── groups:purge-deleted ──────────────────────────────────────────

it('purges groups soft-deleted more than 90 days ago', function (): void {
    $group = Group::factory()->create();
    $group->delete();
    Group::withTrashed()->where('id', $group->id)->toBase()->update(['deleted_at' => now()->subDays(91)]);

    $this->artisan('groups:purge-deleted')->assertSuccessful();

    expect(Group::withTrashed()->find($group->id))->toBeNull();
});

it('does not purge groups soft-deleted less than 90 days ago', function (): void {
    $group = Group::factory()->create();
    $group->delete();
    Group::withTrashed()->where('id', $group->id)->toBase()->update(['deleted_at' => now()->subDays(89)]);

    $this->artisan('groups:purge-deleted')->assertSuccessful();

    expect(Group::withTrashed()->find($group->id))->not->toBeNull();
});

// ── events:generate-recurring ─────────────────────────────────────

it('generates recurring event instances from series RRULE', function (): void {
    $group = Group::factory()->create();
    $user = User::factory()->create();

    $series = EventSeries::factory()->create([
        'group_id' => $group->id,
        'recurrence_rule' => 'FREQ=WEEKLY;BYDAY=MO',
    ]);

    // Create a template event for the series in the past
    Event::factory()->create([
        'group_id' => $group->id,
        'created_by' => $user->id,
        'series_id' => $series->id,
        'status' => EventStatus::Past,
        'starts_at' => now()->subWeek(),
        'ends_at' => now()->subWeek()->addHours(2),
    ]);

    $this->artisan('events:generate-recurring')->assertSuccessful();

    $futureEvents = Event::where('series_id', $series->id)
        ->where('starts_at', '>', now())
        ->count();

    expect($futureEvents)->toBeGreaterThanOrEqual(1);
});

it('skips series that already have sufficient future events', function (): void {
    $group = Group::factory()->create();
    $user = User::factory()->create();

    $series = EventSeries::factory()->create([
        'group_id' => $group->id,
        'recurrence_rule' => 'FREQ=WEEKLY;BYDAY=MO',
    ]);

    // Create 3 future events already
    for ($i = 1; $i <= 3; $i++) {
        Event::factory()->published()->create([
            'group_id' => $group->id,
            'created_by' => $user->id,
            'series_id' => $series->id,
            'starts_at' => now()->addWeeks($i),
            'ends_at' => now()->addWeeks($i)->addHours(2),
        ]);
    }

    $countBefore = Event::where('series_id', $series->id)->count();

    $this->artisan('events:generate-recurring')->assertSuccessful();

    $countAfter = Event::where('series_id', $series->id)->count();

    expect($countAfter)->toBe($countBefore);
});

// ── Schedule Registration ─────────────────────────────────────────

it('registers all commands in the scheduler with correct frequencies', function (): void {
    $schedule = app(Schedule::class);
    $events = collect($schedule->events());

    $commands = $events->map(fn ($event) => [
        'command' => $event->command,
        'expression' => $event->expression,
    ]);

    // events:generate-recurring — daily
    expect($commands->contains(fn ($c) => str_contains($c['command'], 'events:generate-recurring') && $c['expression'] === '0 0 * * *'))->toBeTrue();

    // events:mark-past — hourly
    expect($commands->contains(fn ($c) => str_contains($c['command'], 'events:mark-past') && $c['expression'] === '0 * * * *'))->toBeTrue();

    // accounts:purge-deleted — daily
    expect($commands->contains(fn ($c) => str_contains($c['command'], 'accounts:purge-deleted') && $c['expression'] === '0 0 * * *'))->toBeTrue();

    // groups:purge-deleted — daily
    expect($commands->contains(fn ($c) => str_contains($c['command'], 'groups:purge-deleted') && $c['expression'] === '0 0 * * *'))->toBeTrue();

    // notifications:send-digests — every 5 minutes
    expect($commands->contains(fn ($c) => str_contains($c['command'], 'notifications:send-digests') && $c['expression'] === '*/5 * * * *'))->toBeTrue();
});
