<?php

use App\Enums\EventStatus;
use App\Enums\GroupRole;
use App\Enums\RsvpStatus;
use App\Jobs\PromoteFromWaitlist;
use App\Models\Event;
use App\Models\Group;
use App\Models\Rsvp;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);
});

it('allows a member to leave a group', function (): void {
    $organizer = User::factory()->create();
    $group = Group::factory()->create(['organizer_id' => $organizer->id]);
    $group->members()->attach($organizer->id, [
        'role' => GroupRole::Organizer->value,
        'joined_at' => now(),
    ]);

    $user = User::factory()->create();
    $group->members()->attach($user->id, [
        'role' => GroupRole::Member->value,
        'joined_at' => now(),
    ]);

    $this->actingAs($user)
        ->post(route('groups.leave', $group))
        ->assertRedirect(route('groups.show', $group))
        ->assertSessionHas('status');

    $this->assertDatabaseMissing('group_members', [
        'group_id' => $group->id,
        'user_id' => $user->id,
    ]);
});

it('cancels upcoming RSVPs when leaving a group', function (): void {
    Queue::fake();

    $organizer = User::factory()->create();
    $group = Group::factory()->create(['organizer_id' => $organizer->id]);
    $group->members()->attach($organizer->id, [
        'role' => GroupRole::Organizer->value,
        'joined_at' => now(),
    ]);

    $user = User::factory()->create();
    $group->members()->attach($user->id, [
        'role' => GroupRole::Member->value,
        'joined_at' => now(),
    ]);

    $upcomingEvent = Event::factory()->create([
        'group_id' => $group->id,
        'starts_at' => now()->addWeek(),
        'status' => EventStatus::Published,
    ]);

    $pastEvent = Event::factory()->create([
        'group_id' => $group->id,
        'starts_at' => now()->subWeek(),
        'status' => EventStatus::Published,
    ]);

    $upcomingRsvp = Rsvp::factory()->create([
        'event_id' => $upcomingEvent->id,
        'user_id' => $user->id,
        'status' => RsvpStatus::Going,
        'guest_count' => 1,
    ]);

    $pastRsvp = Rsvp::factory()->create([
        'event_id' => $pastEvent->id,
        'user_id' => $user->id,
        'status' => RsvpStatus::Going,
        'guest_count' => 0,
    ]);

    $this->actingAs($user)
        ->post(route('groups.leave', $group))
        ->assertRedirect(route('groups.show', $group));

    $upcomingRsvp->refresh();
    expect($upcomingRsvp->status)->toBe(RsvpStatus::NotGoing);
    expect($upcomingRsvp->guest_count)->toBe(0);

    $pastRsvp->refresh();
    expect($pastRsvp->status)->toBe(RsvpStatus::Going);

    Queue::assertPushed(PromoteFromWaitlist::class, function ($job) use ($upcomingEvent) {
        return $job->event->id === $upcomingEvent->id;
    });
});

it('prevents the primary organizer from leaving', function (): void {
    $organizer = User::factory()->create();
    $group = Group::factory()->create(['organizer_id' => $organizer->id]);
    $group->members()->attach($organizer->id, [
        'role' => GroupRole::Organizer->value,
        'joined_at' => now(),
    ]);

    $this->actingAs($organizer)
        ->post(route('groups.leave', $group))
        ->assertForbidden();

    $this->assertDatabaseHas('group_members', [
        'group_id' => $group->id,
        'user_id' => $organizer->id,
    ]);
});
