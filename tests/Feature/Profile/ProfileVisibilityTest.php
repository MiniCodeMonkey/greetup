<?php

use App\Enums\ProfileVisibility;
use App\Models\Group;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);
});

// --- Privacy settings toggle ---

it('can toggle profile_visibility to members_only', function () {
    $user = User::factory()->create([
        'profile_visibility' => ProfileVisibility::Public,
    ]);

    $this->actingAs($user)
        ->put('/settings/privacy', [
            'profile_visibility' => 'members_only',
        ])
        ->assertRedirect('/settings?section=privacy');

    expect($user->fresh()->profile_visibility)->toBe(ProfileVisibility::MembersOnly);
});

it('can toggle profile_visibility to public', function () {
    $user = User::factory()->create([
        'profile_visibility' => ProfileVisibility::MembersOnly,
    ]);

    $this->actingAs($user)
        ->put('/settings/privacy', [
            'profile_visibility' => 'public',
        ])
        ->assertRedirect('/settings?section=privacy');

    expect($user->fresh()->profile_visibility)->toBe(ProfileVisibility::Public);
});

it('rejects invalid profile_visibility values', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->put('/settings/privacy', [
            'profile_visibility' => 'invalid_value',
        ])
        ->assertSessionHasErrors('profile_visibility');
});

it('requires authentication to update privacy settings', function () {
    $this->put('/settings/privacy', [
        'profile_visibility' => 'public',
    ])->assertRedirect('/login');
});

// --- Profile visibility enforcement ---

it('allows any authenticated user to view a public profile', function () {
    $profileOwner = User::factory()->create([
        'profile_visibility' => ProfileVisibility::Public,
    ]);
    $viewer = User::factory()->create();

    $this->actingAs($viewer);

    expect($viewer->can('view', $profileOwner))->toBeTrue();
});

it('allows the owner to view their own members_only profile', function () {
    $user = User::factory()->create([
        'profile_visibility' => ProfileVisibility::MembersOnly,
    ]);

    expect($user->can('view', $user))->toBeTrue();
});

it('allows a user who shares a group to view a members_only profile', function () {
    $profileOwner = User::factory()->create([
        'profile_visibility' => ProfileVisibility::MembersOnly,
    ]);
    $viewer = User::factory()->create();

    $group = Group::factory()->create();
    $group->members()->attach($profileOwner->id, ['role' => 'member', 'joined_at' => now()]);
    $group->members()->attach($viewer->id, ['role' => 'member', 'joined_at' => now()]);

    expect($viewer->can('view', $profileOwner))->toBeTrue();
});

it('denies a user who does not share a group from viewing a members_only profile', function () {
    $profileOwner = User::factory()->create([
        'profile_visibility' => ProfileVisibility::MembersOnly,
    ]);
    $viewer = User::factory()->create();

    expect($viewer->can('view', $profileOwner))->toBeFalse();
});

// --- Scout search index ---

it('is searchable when profile_visibility is public', function () {
    $user = User::factory()->create([
        'profile_visibility' => ProfileVisibility::Public,
    ]);

    expect($user->shouldBeSearchable())->toBeTrue();
});

it('is not searchable when profile_visibility is members_only', function () {
    $user = User::factory()->create([
        'profile_visibility' => ProfileVisibility::MembersOnly,
    ]);

    expect($user->shouldBeSearchable())->toBeFalse();
});

it('updates searchability when visibility changes', function () {
    $user = User::factory()->create([
        'profile_visibility' => ProfileVisibility::Public,
    ]);

    expect($user->shouldBeSearchable())->toBeTrue();

    $user->update(['profile_visibility' => ProfileVisibility::MembersOnly]);

    expect($user->fresh()->shouldBeSearchable())->toBeFalse();
});
