<?php

use App\Enums\EventType;
use App\Models\Event;
use App\Models\Group;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);
});

it('returns a valid .ics file with correct format', function (): void {
    $organizer = User::factory()->create();
    $group = Group::factory()->create(['name' => 'Laravel Copenhagen', 'organizer_id' => $organizer->id]);
    $event = Event::factory()->published()->create([
        'group_id' => $group->id,
        'created_by' => $organizer->id,
        'name' => 'March Meetup',
        'description' => 'A great meetup about Laravel.',
        'event_type' => EventType::InPerson,
        'venue_name' => 'Tech Hub',
        'venue_address' => '123 Main St, Copenhagen',
        'starts_at' => '2026-04-15 18:00:00',
        'ends_at' => '2026-04-15 20:00:00',
        'timezone' => 'Europe/Copenhagen',
    ]);

    $response = $this->get(route('events.calendar', [$group, $event]));

    $response->assertOk()
        ->assertHeader('content-type', 'text/calendar; charset=utf-8');

    $content = $response->streamedContent();

    expect($content)
        ->toContain('BEGIN:VCALENDAR')
        ->toContain('VERSION:2.0')
        ->toContain('BEGIN:VEVENT')
        ->toContain('END:VEVENT')
        ->toContain('END:VCALENDAR');
});

it('includes correct DTSTART and DTEND timestamps in UTC', function (): void {
    $organizer = User::factory()->create();
    $group = Group::factory()->create(['organizer_id' => $organizer->id]);
    $event = Event::factory()->published()->create([
        'group_id' => $group->id,
        'created_by' => $organizer->id,
        'starts_at' => '2026-04-15 18:00:00',
        'ends_at' => '2026-04-15 20:00:00',
    ]);

    $response = $this->get(route('events.calendar', [$group, $event]));
    $content = $response->streamedContent();

    expect($content)
        ->toContain('DTSTART:20260415T180000Z')
        ->toContain('DTEND:20260415T200000Z');
});

it('includes event name as SUMMARY', function (): void {
    $organizer = User::factory()->create();
    $group = Group::factory()->create(['organizer_id' => $organizer->id]);
    $event = Event::factory()->published()->create([
        'group_id' => $group->id,
        'created_by' => $organizer->id,
        'name' => 'Spring Workshop 2026',
    ]);

    $response = $this->get(route('events.calendar', [$group, $event]));
    $content = $response->streamedContent();

    expect($content)->toContain('SUMMARY:Spring Workshop 2026');
});

it('includes plain text description as DESCRIPTION', function (): void {
    $organizer = User::factory()->create();
    $group = Group::factory()->create(['organizer_id' => $organizer->id]);
    $event = Event::factory()->published()->create([
        'group_id' => $group->id,
        'created_by' => $organizer->id,
        'description' => 'Join us for an evening of code.',
    ]);

    $response = $this->get(route('events.calendar', [$group, $event]));
    $content = $response->streamedContent();

    expect($content)->toContain('DESCRIPTION:Join us for an evening of code.');
});

it('includes venue address as LOCATION for in-person events', function (): void {
    $organizer = User::factory()->create();
    $group = Group::factory()->create(['organizer_id' => $organizer->id]);
    $event = Event::factory()->published()->create([
        'group_id' => $group->id,
        'created_by' => $organizer->id,
        'event_type' => EventType::InPerson,
        'venue_address' => '123 Main St, Copenhagen',
    ]);

    $response = $this->get(route('events.calendar', [$group, $event]));
    $content = $response->streamedContent();

    expect($content)->toContain('LOCATION:');
    expect($content)->toContain('123 Main St');
    expect($content)->toContain('Copenhagen');
});

it('includes online link as LOCATION for online events', function (): void {
    $organizer = User::factory()->create();
    $group = Group::factory()->create(['organizer_id' => $organizer->id]);
    $event = Event::factory()->online()->published()->create([
        'group_id' => $group->id,
        'created_by' => $organizer->id,
        'online_link' => 'https://meet.example.com/room-42',
    ]);

    $response = $this->get(route('events.calendar', [$group, $event]));
    $content = $response->streamedContent();

    expect($content)->toContain('LOCATION:https://meet.example.com/room-42');
});

it('includes group name as ORGANIZER', function (): void {
    $organizer = User::factory()->create();
    $group = Group::factory()->create(['name' => 'Laravel Copenhagen', 'organizer_id' => $organizer->id]);
    $event = Event::factory()->published()->create([
        'group_id' => $group->id,
        'created_by' => $organizer->id,
    ]);

    $response = $this->get(route('events.calendar', [$group, $event]));
    $content = $response->streamedContent();

    expect($content)->toContain('ORGANIZER:Laravel Copenhagen');
});

it('returns 404 when event does not belong to group', function (): void {
    $organizer = User::factory()->create();
    $group = Group::factory()->create(['organizer_id' => $organizer->id]);
    $otherGroup = Group::factory()->create(['organizer_id' => $organizer->id]);
    $event = Event::factory()->published()->create([
        'group_id' => $otherGroup->id,
        'created_by' => $organizer->id,
    ]);

    $response = $this->get(route('events.calendar', [$group, $event]));

    $response->assertNotFound();
});
