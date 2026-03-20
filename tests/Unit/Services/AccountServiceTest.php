<?php

use App\Enums\RsvpStatus;
use App\Models\Event;
use App\Models\Group;
use App\Models\Rsvp;
use App\Models\User;
use App\Services\AccountService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);
    $this->service = new AccountService;
});

// --- Delete Account ---

it('soft deletes a user account', function (): void {
    $user = User::factory()->create();

    $this->service->deleteAccount($user);

    expect($user->trashed())->toBeTrue();
    expect(User::withTrashed()->find($user->id))->not->toBeNull();
});

it('throws when account is already deleted', function (): void {
    $user = User::factory()->create();
    $user->delete();

    $this->service->deleteAccount($user);
})->throws(InvalidArgumentException::class, 'Account is already deleted.');

it('throws when user organizes active groups', function (): void {
    $user = User::factory()->create();
    Group::factory()->create(['organizer_id' => $user->id, 'is_active' => true]);

    $this->service->deleteAccount($user);
})->throws(InvalidArgumentException::class, 'You must transfer ownership of your groups before deleting your account.');

it('allows deletion when user organizes only inactive groups', function (): void {
    $user = User::factory()->create();
    Group::factory()->create(['organizer_id' => $user->id, 'is_active' => false]);

    $this->service->deleteAccount($user);

    expect($user->trashed())->toBeTrue();
});

// --- Export Data ---

it('exports user data as JSON', function (): void {
    $user = User::factory()->create([
        'name' => 'Test User',
        'email' => 'test@example.com',
        'bio' => 'Hello world',
    ]);

    $json = $this->service->exportData($user);
    $data = json_decode($json, true);

    expect($data)->toHaveKeys(['profile', 'groups', 'rsvps'])
        ->and($data['profile']['name'])->toBe('Test User')
        ->and($data['profile']['email'])->toBe('test@example.com')
        ->and($data['profile']['bio'])->toBe('Hello world');
});

it('includes group membership in export', function (): void {
    $user = User::factory()->create();
    $group = Group::factory()->create();
    $group->members()->attach($user, ['role' => 'member', 'joined_at' => now()]);

    $json = $this->service->exportData($user);
    $data = json_decode($json, true);

    expect($data['groups'])->toHaveCount(1)
        ->and($data['groups'][0]['name'])->toBe($group->name)
        ->and($data['groups'][0]['role'])->toBe('member');
});

it('includes RSVP data in export', function (): void {
    $user = User::factory()->create();
    $event = Event::factory()->create();

    Rsvp::create([
        'event_id' => $event->id,
        'user_id' => $user->id,
        'status' => RsvpStatus::Going,
        'guest_count' => 1,
        'checked_in' => false,
    ]);

    $json = $this->service->exportData($user);
    $data = json_decode($json, true);

    expect($data['rsvps'])->toHaveCount(1)
        ->and($data['rsvps'][0]['status'])->toBe('going')
        ->and($data['rsvps'][0]['guest_count'])->toBe(1);
});

// --- Suspend Account ---

it('suspends a user account', function (): void {
    $user = User::factory()->create(['is_suspended' => false]);

    $this->service->suspendAccount($user, 'Violation of terms');

    $user->refresh();
    expect($user->is_suspended)->toBeTrue()
        ->and($user->suspended_at)->not->toBeNull()
        ->and($user->suspended_reason)->toBe('Violation of terms');
});

it('throws when suspending already suspended account', function (): void {
    $user = User::factory()->create(['is_suspended' => true, 'suspended_at' => now()]);

    $this->service->suspendAccount($user, 'Reason');
})->throws(InvalidArgumentException::class, 'Account is already suspended.');

// --- Unsuspend Account ---

it('unsuspends a user account', function (): void {
    $user = User::factory()->create([
        'is_suspended' => true,
        'suspended_at' => now(),
        'suspended_reason' => 'Some reason',
    ]);

    $this->service->unsuspendAccount($user);

    $user->refresh();
    expect($user->is_suspended)->toBeFalse()
        ->and($user->suspended_at)->toBeNull()
        ->and($user->suspended_reason)->toBeNull();
});

it('throws when unsuspending non-suspended account', function (): void {
    $user = User::factory()->create(['is_suspended' => false]);

    $this->service->unsuspendAccount($user);
})->throws(InvalidArgumentException::class, 'Account is not suspended.');
