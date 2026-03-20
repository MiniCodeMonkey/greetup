<?php

use App\Enums\RsvpStatus;
use App\Models\Event;
use App\Models\Group;
use App\Models\Rsvp;
use App\Models\User;
use App\Services\ExportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);
    $this->service = new ExportService;
});

// --- Export Members ---

it('exports members CSV with correct headers', function (): void {
    $group = Group::factory()->create();

    $csv = $this->service->exportMembers($group);

    $lines = explode("\n", trim($csv));
    expect($lines[0])->toBe('Name,Email,"Joined Date","Events Attended",No-Shows');
});

it('exports members with attendance stats', function (): void {
    $group = Group::factory()->create();
    $user = User::factory()->create(['name' => 'Jane Doe', 'email' => 'jane@example.com']);
    $group->members()->attach($user, ['role' => 'member', 'joined_at' => now()]);

    $event = Event::factory()->create(['group_id' => $group->id]);
    Rsvp::create([
        'event_id' => $event->id,
        'user_id' => $user->id,
        'status' => RsvpStatus::Going,
        'guest_count' => 0,
    ]);

    $csv = $this->service->exportMembers($group);

    $lines = explode("\n", trim($csv));
    expect(count($lines))->toBe(2); // header + 1 member
    expect($lines[1])->toContain('Jane Doe')
        ->and($lines[1])->toContain('jane@example.com');
});

it('exports empty members list for group with no members', function (): void {
    $group = Group::factory()->create();

    $csv = $this->service->exportMembers($group);

    $lines = explode("\n", trim($csv));
    expect(count($lines))->toBe(1); // header only
});

// --- Export Attendees ---

it('exports attendees CSV with correct headers', function (): void {
    $event = Event::factory()->create();

    $csv = $this->service->exportAttendees($event);

    $lines = explode("\n", trim($csv));
    expect($lines[0])->toBe('Name,"RSVP Status","Guest Count","Checked In"');
});

it('exports attendees with RSVP data', function (): void {
    $event = Event::factory()->create();
    $user = User::factory()->create(['name' => 'John Smith']);

    Rsvp::create([
        'event_id' => $event->id,
        'user_id' => $user->id,
        'status' => RsvpStatus::Going,
        'guest_count' => 2,
        'checked_in' => true,
    ]);

    $csv = $this->service->exportAttendees($event);

    $lines = explode("\n", trim($csv));
    expect(count($lines))->toBe(2); // header + 1 attendee
    expect($lines[1])->toContain('John Smith')
        ->and($lines[1])->toContain('going')
        ->and($lines[1])->toContain('2')
        ->and($lines[1])->toContain('Yes');
});

it('exports attendees with checked-in No for unchecked', function (): void {
    $event = Event::factory()->create();
    $user = User::factory()->create();

    Rsvp::create([
        'event_id' => $event->id,
        'user_id' => $user->id,
        'status' => RsvpStatus::Going,
        'guest_count' => 0,
        'checked_in' => false,
    ]);

    $csv = $this->service->exportAttendees($event);

    $lines = explode("\n", trim($csv));
    expect($lines[1])->toContain('No');
});
