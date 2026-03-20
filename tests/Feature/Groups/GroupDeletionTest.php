<?php

use App\Enums\EventStatus;
use App\Enums\GroupRole;
use App\Models\Event;
use App\Models\Group;
use App\Models\User;
use App\Notifications\EventCancelled;
use App\Notifications\GroupDeleted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);
    Notification::fake();

    $this->organizer = User::factory()->create([
        'password' => Hash::make('password'),
    ]);
    $this->group = Group::factory()->create([
        'organizer_id' => $this->organizer->id,
    ]);
    $this->group->members()->attach($this->organizer->id, [
        'role' => GroupRole::Organizer->value,
        'joined_at' => now(),
    ]);

    $this->member = User::factory()->create();
    $this->group->members()->attach($this->member->id, [
        'role' => GroupRole::Member->value,
        'joined_at' => now(),
    ]);
});

// --- Successful Deletion ---

it('soft-deletes the group when organizer provides correct password', function (): void {
    $this->actingAs($this->organizer)
        ->delete(route('groups.destroy', $this->group), [
            'password' => 'password',
        ])
        ->assertRedirect(route('dashboard'))
        ->assertSessionHas('status');

    $this->assertSoftDeleted('groups', ['id' => $this->group->id]);
});

it('cancels all upcoming events when group is deleted', function (): void {
    $upcomingEvent = Event::factory()->published()->create([
        'group_id' => $this->group->id,
        'created_by' => $this->organizer->id,
        'starts_at' => now()->addWeek(),
    ]);

    $pastEvent = Event::factory()->past()->create([
        'group_id' => $this->group->id,
        'created_by' => $this->organizer->id,
    ]);

    $this->actingAs($this->organizer)
        ->delete(route('groups.destroy', $this->group), [
            'password' => 'password',
        ]);

    $upcomingEvent->refresh();
    expect($upcomingEvent->status)->toBe(EventStatus::Cancelled);
    expect($upcomingEvent->cancelled_at)->not->toBeNull();

    $pastEvent->refresh();
    expect($pastEvent->status)->toBe(EventStatus::Past);
});

it('sends EventCancelled notifications for upcoming events', function (): void {
    $upcomingEvent = Event::factory()->published()->create([
        'group_id' => $this->group->id,
        'created_by' => $this->organizer->id,
        'starts_at' => now()->addWeek(),
    ]);

    $this->actingAs($this->organizer)
        ->delete(route('groups.destroy', $this->group), [
            'password' => 'password',
        ]);

    Notification::assertSentTo($this->member, EventCancelled::class, function ($notification) use ($upcomingEvent) {
        return $notification->event->id === $upcomingEvent->id
            && $notification->group->id === $this->group->id;
    });
});

it('sends GroupDeleted notification to all members', function (): void {
    $this->actingAs($this->organizer)
        ->delete(route('groups.destroy', $this->group), [
            'password' => 'password',
        ]);

    Notification::assertSentTo($this->member, GroupDeleted::class, function ($notification) {
        return $notification->group->id === $this->group->id;
    });

    Notification::assertSentTo($this->organizer, GroupDeleted::class);
});

// --- Wrong Password ---

it('rejects deletion with wrong password', function (): void {
    $this->actingAs($this->organizer)
        ->delete(route('groups.destroy', $this->group), [
            'password' => 'wrong-password',
        ])
        ->assertSessionHasErrors('password');

    $this->assertDatabaseHas('groups', ['id' => $this->group->id, 'deleted_at' => null]);
    Notification::assertNotSentTo($this->member, GroupDeleted::class);
});

// --- Authorization ---

it('forbids non-organizer from deleting the group', function (): void {
    $this->actingAs($this->member)
        ->delete(route('groups.destroy', $this->group), [
            'password' => 'password',
        ])
        ->assertForbidden();

    $this->assertDatabaseHas('groups', ['id' => $this->group->id, 'deleted_at' => null]);
});

it('forbids co-organizer from deleting the group', function (): void {
    $coOrganizer = User::factory()->create([
        'password' => Hash::make('password'),
    ]);
    $this->group->members()->attach($coOrganizer->id, [
        'role' => GroupRole::CoOrganizer->value,
        'joined_at' => now(),
    ]);

    $this->actingAs($coOrganizer)
        ->delete(route('groups.destroy', $this->group), [
            'password' => 'password',
        ])
        ->assertForbidden();
});
