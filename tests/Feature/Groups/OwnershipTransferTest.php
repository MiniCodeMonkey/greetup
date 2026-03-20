<?php

use App\Enums\GroupRole;
use App\Models\Group;
use App\Models\User;
use App\Notifications\OwnershipTransferred;
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

    $this->coOrganizer = User::factory()->create();
    $this->group->members()->attach($this->coOrganizer->id, [
        'role' => GroupRole::CoOrganizer->value,
        'joined_at' => now(),
    ]);
});

// --- Page Display ---

it('displays the ownership transfer form for the organizer', function (): void {
    $this->actingAs($this->organizer)
        ->get(route('groups.manage.transfer', $this->group))
        ->assertOk()
        ->assertViewIs('groups.manage.transfer')
        ->assertViewHas('coOrganizers')
        ->assertSee('Transfer Ownership');
});

it('denies access to the transfer form for non-organizers', function (): void {
    $this->actingAs($this->coOrganizer)
        ->get(route('groups.manage.transfer', $this->group))
        ->assertForbidden();
});

// --- Successful Transfer ---

it('transfers ownership to a co-organizer', function (): void {
    $this->actingAs($this->organizer)
        ->post(route('groups.manage.transfer.update', $this->group), [
            'new_owner_id' => $this->coOrganizer->id,
            'password' => 'password',
        ])
        ->assertRedirect(route('groups.show', $this->group))
        ->assertSessionHas('status', 'Group ownership has been transferred successfully.');

    $this->group->refresh();
    expect($this->group->organizer_id)->toBe($this->coOrganizer->id);

    $this->assertDatabaseHas('group_members', [
        'group_id' => $this->group->id,
        'user_id' => $this->coOrganizer->id,
        'role' => GroupRole::Organizer->value,
    ]);

    $this->assertDatabaseHas('group_members', [
        'group_id' => $this->group->id,
        'user_id' => $this->organizer->id,
        'role' => GroupRole::CoOrganizer->value,
    ]);

    Notification::assertSentTo($this->coOrganizer, OwnershipTransferred::class, function ($notification) {
        return $notification->group->id === $this->group->id;
    });
});

// --- Wrong Password ---

it('rejects transfer with wrong password', function (): void {
    $this->actingAs($this->organizer)
        ->post(route('groups.manage.transfer.update', $this->group), [
            'new_owner_id' => $this->coOrganizer->id,
            'password' => 'wrong-password',
        ])
        ->assertSessionHasErrors('password');

    $this->group->refresh();
    expect($this->group->organizer_id)->toBe($this->organizer->id);

    Notification::assertNotSentTo($this->coOrganizer, OwnershipTransferred::class);
});

// --- Non-Co-Organizer Target ---

it('rejects transfer to a non-co-organizer member', function (): void {
    $member = User::factory()->create();
    $this->group->members()->attach($member->id, [
        'role' => GroupRole::Member->value,
        'joined_at' => now(),
    ]);

    $this->actingAs($this->organizer)
        ->post(route('groups.manage.transfer.update', $this->group), [
            'new_owner_id' => $member->id,
            'password' => 'password',
        ])
        ->assertSessionHasErrors('new_owner_id');

    $this->group->refresh();
    expect($this->group->organizer_id)->toBe($this->organizer->id);
});

it('rejects transfer to a non-member', function (): void {
    $stranger = User::factory()->create();

    $this->actingAs($this->organizer)
        ->post(route('groups.manage.transfer.update', $this->group), [
            'new_owner_id' => $stranger->id,
            'password' => 'password',
        ])
        ->assertSessionHasErrors('new_owner_id');

    $this->group->refresh();
    expect($this->group->organizer_id)->toBe($this->organizer->id);
});

// --- Authorization ---

it('forbids co-organizer from transferring ownership', function (): void {
    $this->actingAs($this->coOrganizer)
        ->post(route('groups.manage.transfer.update', $this->group), [
            'new_owner_id' => $this->coOrganizer->id,
            'password' => 'password',
        ])
        ->assertForbidden();
});
