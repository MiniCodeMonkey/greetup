<?php

use App\Enums\AttendanceResult;
use App\Enums\GroupRole;
use App\Enums\RsvpStatus;
use App\Models\Event;
use App\Models\Feedback;
use App\Models\Group;
use App\Models\Rsvp;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);
    $this->withoutVite();
});

it('requires authentication to access analytics', function (): void {
    $group = Group::factory()->create();

    $this->get(route('groups.manage.analytics', $group))
        ->assertRedirect(route('login'));
});

it('rejects a regular member from accessing analytics', function (): void {
    $group = Group::factory()->create();
    $member = User::factory()->create();

    $group->members()->attach($member->id, [
        'role' => GroupRole::Member->value,
        'joined_at' => now(),
    ]);

    $this->actingAs($member)
        ->get(route('groups.manage.analytics', $group))
        ->assertForbidden();
});

it('rejects an event organizer from accessing analytics', function (): void {
    $group = Group::factory()->create();
    $eventOrganizer = User::factory()->create();

    $group->members()->attach($eventOrganizer->id, [
        'role' => GroupRole::EventOrganizer->value,
        'joined_at' => now(),
    ]);

    $this->actingAs($eventOrganizer)
        ->get(route('groups.manage.analytics', $group))
        ->assertForbidden();
});

it('rejects an assistant organizer from accessing analytics', function (): void {
    $group = Group::factory()->create();
    $assistant = User::factory()->create();

    $group->members()->attach($assistant->id, [
        'role' => GroupRole::AssistantOrganizer->value,
        'joined_at' => now(),
    ]);

    $this->actingAs($assistant)
        ->get(route('groups.manage.analytics', $group))
        ->assertForbidden();
});

it('allows a co-organizer to access analytics', function (): void {
    $group = Group::factory()->create();
    $coOrganizer = User::factory()->create();

    $group->members()->attach($coOrganizer->id, [
        'role' => GroupRole::CoOrganizer->value,
        'joined_at' => now(),
    ]);

    $this->actingAs($coOrganizer)
        ->get(route('groups.manage.analytics', $group))
        ->assertOk()
        ->assertSee('Group Analytics');
});

it('allows the organizer to access analytics', function (): void {
    $group = Group::factory()->create();
    $organizer = $group->organizer;

    $group->members()->attach($organizer->id, [
        'role' => GroupRole::Organizer->value,
        'joined_at' => now(),
    ]);

    $this->actingAs($organizer)
        ->get(route('groups.manage.analytics', $group))
        ->assertOk()
        ->assertSee('Group Analytics');
});

it('shows member growth data', function (): void {
    $group = Group::factory()->create();
    $coOrganizer = User::factory()->create();

    $group->members()->attach($coOrganizer->id, [
        'role' => GroupRole::CoOrganizer->value,
        'joined_at' => now(),
    ]);

    $members = User::factory()->count(3)->create();
    foreach ($members as $member) {
        $group->members()->attach($member->id, [
            'role' => GroupRole::Member->value,
            'joined_at' => now(),
        ]);
    }

    $response = $this->actingAs($coOrganizer)
        ->get(route('groups.manage.analytics', $group));

    $response->assertOk()
        ->assertSee('Member Growth')
        ->assertSee('New Members');
});

it('shows event count over time', function (): void {
    $group = Group::factory()->create();
    $coOrganizer = User::factory()->create();

    $group->members()->attach($coOrganizer->id, [
        'role' => GroupRole::CoOrganizer->value,
        'joined_at' => now(),
    ]);

    Event::factory()->count(2)->for($group)->past()->create();
    Event::factory()->for($group)->published()->create();

    $response = $this->actingAs($coOrganizer)
        ->get(route('groups.manage.analytics', $group));

    $response->assertOk()
        ->assertSee('Events Over Time');
});

it('shows correct average attendance rate', function (): void {
    $group = Group::factory()->create();
    $coOrganizer = User::factory()->create();

    $group->members()->attach($coOrganizer->id, [
        'role' => GroupRole::CoOrganizer->value,
        'joined_at' => now(),
    ]);

    $event = Event::factory()->for($group)->past()->create();

    // 3 attended, 1 no-show = 75% attendance rate
    Rsvp::factory()->count(3)->for($event)->going()->create([
        'attended' => AttendanceResult::Attended,
    ]);
    Rsvp::factory()->for($event)->going()->create([
        'attended' => AttendanceResult::NoShow,
    ]);

    $response = $this->actingAs($coOrganizer)
        ->get(route('groups.manage.analytics', $group));

    $response->assertOk()
        ->assertSee('Average Attendance Rate')
        ->assertSee('75');
});

it('shows most active members by attendance', function (): void {
    $group = Group::factory()->create();
    $coOrganizer = User::factory()->create();

    $group->members()->attach($coOrganizer->id, [
        'role' => GroupRole::CoOrganizer->value,
        'joined_at' => now(),
    ]);

    $activeMember = User::factory()->create(['name' => 'Super Active User']);
    $events = Event::factory()->count(3)->for($group)->past()->create();

    foreach ($events as $event) {
        Rsvp::factory()->for($event)->create([
            'user_id' => $activeMember->id,
            'status' => RsvpStatus::Going,
            'attended' => AttendanceResult::Attended,
        ]);
    }

    $response = $this->actingAs($coOrganizer)
        ->get(route('groups.manage.analytics', $group));

    $response->assertOk()
        ->assertSee('Most Active Members')
        ->assertSee('Super Active User')
        ->assertSee('3');
});

it('shows average event rating', function (): void {
    $group = Group::factory()->create();
    $coOrganizer = User::factory()->create();

    $group->members()->attach($coOrganizer->id, [
        'role' => GroupRole::CoOrganizer->value,
        'joined_at' => now(),
    ]);

    $event = Event::factory()->for($group)->past()->create();

    // Ratings: 4, 5, 3 => avg = 4.0
    Feedback::factory()->for($event)->create(['rating' => 4]);
    Feedback::factory()->for($event)->create(['rating' => 5]);
    Feedback::factory()->for($event)->create(['rating' => 3]);

    $response = $this->actingAs($coOrganizer)
        ->get(route('groups.manage.analytics', $group));

    $response->assertOk()
        ->assertSee('Average Event Rating')
        ->assertSee('4');
});

it('handles group with no events gracefully', function (): void {
    $group = Group::factory()->create();
    $coOrganizer = User::factory()->create();

    $group->members()->attach($coOrganizer->id, [
        'role' => GroupRole::CoOrganizer->value,
        'joined_at' => now(),
    ]);

    $response = $this->actingAs($coOrganizer)
        ->get(route('groups.manage.analytics', $group));

    $response->assertOk()
        ->assertSee('0%')
        ->assertSee('0')
        ->assertSee('/5');
});
