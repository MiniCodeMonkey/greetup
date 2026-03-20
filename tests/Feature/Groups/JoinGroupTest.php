<?php

use App\Enums\GroupRole;
use App\Models\Group;
use App\Models\User;
use App\Notifications\WelcomeToGroup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);
});

it('allows a verified user to join an open group', function (): void {
    Notification::fake();

    $organizer = User::factory()->create();
    $group = Group::factory()->create(['organizer_id' => $organizer->id]);
    $group->members()->attach($organizer->id, [
        'role' => GroupRole::Organizer->value,
        'joined_at' => now(),
    ]);

    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('groups.join', $group))
        ->assertRedirect(route('groups.show', $group))
        ->assertSessionHas('status');

    $this->assertDatabaseHas('group_members', [
        'group_id' => $group->id,
        'user_id' => $user->id,
        'role' => GroupRole::Member->value,
    ]);

    $member = $group->members()->where('user_id', $user->id)->first();
    expect($member->pivot->joined_at)->not->toBeNull();

    Notification::assertSentTo($user, WelcomeToGroup::class, function ($notification) use ($group) {
        return $notification->group->id === $group->id;
    });
});

it('rejects joining if user is already a member', function (): void {
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
        ->post(route('groups.join', $group))
        ->assertForbidden();
});

it('rejects joining if user is banned from the group', function (): void {
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
        'is_banned' => true,
        'banned_at' => now(),
        'banned_reason' => 'Spam',
    ]);

    $this->actingAs($user)
        ->post(route('groups.join', $group))
        ->assertForbidden();
});

it('rejects joining if group is at max members capacity', function (): void {
    Notification::fake();

    $organizer = User::factory()->create();
    $group = Group::factory()->create([
        'organizer_id' => $organizer->id,
        'max_members' => 2,
    ]);
    $group->members()->attach($organizer->id, [
        'role' => GroupRole::Organizer->value,
        'joined_at' => now(),
    ]);

    $existingMember = User::factory()->create();
    $group->members()->attach($existingMember->id, [
        'role' => GroupRole::Member->value,
        'joined_at' => now(),
    ]);

    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('groups.join', $group))
        ->assertStatus(500);
});

it('rejects joining if user is unverified', function (): void {
    $organizer = User::factory()->create();
    $group = Group::factory()->create(['organizer_id' => $organizer->id]);
    $group->members()->attach($organizer->id, [
        'role' => GroupRole::Organizer->value,
        'joined_at' => now(),
    ]);

    $user = User::factory()->unverified()->create();

    $this->actingAs($user)
        ->post(route('groups.join', $group))
        ->assertForbidden();
});
