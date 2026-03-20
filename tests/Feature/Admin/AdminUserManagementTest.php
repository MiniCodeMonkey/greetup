<?php

use App\Models\Event;
use App\Models\Group;
use App\Models\Rsvp;
use App\Models\User;
use App\Notifications\AccountSuspended;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
    $this->withoutVite();
});

// --- Access Control ---

it('allows admin to access user list', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $response = $this->actingAs($admin)->get(route('admin.users.index'));

    $response->assertOk();
    $response->assertSee('Manage Users');
});

it('returns 403 for regular users accessing user list', function (): void {
    $user = User::factory()->create();
    $user->assignRole('user');

    $response = $this->actingAs($user)->get(route('admin.users.index'));

    $response->assertForbidden();
});

it('redirects unauthenticated users to login for user list', function (): void {
    $response = $this->get(route('admin.users.index'));

    $response->assertRedirect(route('login'));
});

it('returns 403 for regular users accessing user detail', function (): void {
    $user = User::factory()->create();
    $user->assignRole('user');
    $target = User::factory()->create();

    $response = $this->actingAs($user)->get(route('admin.users.show', $target));

    $response->assertForbidden();
});

it('returns 403 for regular users attempting to suspend', function (): void {
    $user = User::factory()->create();
    $user->assignRole('user');
    $target = User::factory()->create();

    $response = $this->actingAs($user)->post(route('admin.users.suspend', $target), [
        'reason' => 'Test',
    ]);

    $response->assertForbidden();
});

it('returns 403 for regular users attempting to delete', function (): void {
    $user = User::factory()->create();
    $user->assignRole('user');
    $target = User::factory()->create();

    $response = $this->actingAs($user)->delete(route('admin.users.destroy', $target));

    $response->assertForbidden();
});

// --- User List ---

it('displays users with pagination at 25 per page', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    User::factory()->count(30)->create();

    $response = $this->actingAs($admin)->get(route('admin.users.index'));

    $response->assertOk();
    // 25 per page + admin = 31 total, page 1 shows 25
    $response->assertSee('Next');
});

it('searches users by name', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $matchingUser = User::factory()->create(['name' => 'Unique Searchable Name']);
    User::factory()->create(['name' => 'Other Person']);

    $response = $this->actingAs($admin)->get(route('admin.users.index', ['search' => 'Unique Searchable']));

    $response->assertOk();
    $response->assertSee('Unique Searchable Name');
    $response->assertDontSee('Other Person');
});

it('searches users by email', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $matchingUser = User::factory()->create(['email' => 'findme@example.com']);
    User::factory()->create(['email' => 'other@example.com']);

    $response = $this->actingAs($admin)->get(route('admin.users.index', ['search' => 'findme@example']));

    $response->assertOk();
    $response->assertSee('findme@example.com');
    $response->assertDontSee('other@example.com');
});

it('filters suspended users', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $suspendedUser = User::factory()->suspended()->create(['name' => 'Suspended Person']);
    $activeUser = User::factory()->create(['name' => 'Active Person']);

    $response = $this->actingAs($admin)->get(route('admin.users.index', ['suspended' => '1']));

    $response->assertOk();
    $response->assertSee('Suspended Person');
    $response->assertDontSee('Active Person');
});

// --- User Detail ---

it('shows user details with groups and events', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $user = User::factory()->create(['name' => 'Detail User']);
    $group = Group::factory()->create();
    $group->members()->attach($user->id, [
        'role' => 'member',
        'joined_at' => now(),
    ]);

    $event = Event::factory()->for($group)->create(['name' => 'Test Event']);
    Rsvp::factory()->create([
        'user_id' => $user->id,
        'event_id' => $event->id,
    ]);

    $response = $this->actingAs($admin)->get(route('admin.users.show', $user));

    $response->assertOk();
    $response->assertSee('Detail User');
    $response->assertSee($group->name);
    $response->assertSee('Test Event');
});

// --- Suspend ---

it('suspends a user and sends notification', function (): void {
    Notification::fake();

    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $user = User::factory()->create(['name' => 'To Suspend']);

    $response = $this->actingAs($admin)->post(route('admin.users.suspend', $user), [
        'reason' => 'Violated community guidelines',
    ]);

    $response->assertRedirect(route('admin.users.show', $user));

    $user->refresh();
    expect($user->is_suspended)->toBeTrue();
    expect($user->suspended_at)->not->toBeNull();
    expect($user->suspended_reason)->toBe('Violated community guidelines');

    Notification::assertSentTo($user, AccountSuspended::class);
});

it('requires a reason to suspend a user', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $user = User::factory()->create();

    $response = $this->actingAs($admin)->post(route('admin.users.suspend', $user), [
        'reason' => '',
    ]);

    $response->assertSessionHasErrors('reason');
});

// --- Unsuspend ---

it('unsuspends a user and clears suspension fields', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $user = User::factory()->suspended()->create();

    $response = $this->actingAs($admin)->post(route('admin.users.unsuspend', $user));

    $response->assertRedirect(route('admin.users.show', $user));

    $user->refresh();
    expect($user->is_suspended)->toBeFalse();
    expect($user->suspended_at)->toBeNull();
    expect($user->suspended_reason)->toBeNull();
});

// --- Delete ---

it('hard deletes a user', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $user = User::factory()->create(['name' => 'To Delete']);
    $userId = $user->id;

    $response = $this->actingAs($admin)->delete(route('admin.users.destroy', $user));

    $response->assertRedirect(route('admin.users.index'));

    // Hard delete - should not exist even with trashed
    expect(User::withTrashed()->find($userId))->toBeNull();
});
