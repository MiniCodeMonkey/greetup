<?php

use App\Models\Group;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
    $this->withoutVite();
});

// --- Access Control ---

it('allows admin to access group list', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $response = $this->actingAs($admin)->get(route('admin.groups.index'));

    $response->assertOk();
    $response->assertSee('Manage Groups');
});

it('returns 403 for regular users accessing group list', function (): void {
    $user = User::factory()->create();
    $user->assignRole('user');

    $response = $this->actingAs($user)->get(route('admin.groups.index'));

    $response->assertForbidden();
});

it('redirects unauthenticated users to login for group list', function (): void {
    $response = $this->get(route('admin.groups.index'));

    $response->assertRedirect(route('login'));
});

it('returns 403 for regular users accessing group detail', function (): void {
    $user = User::factory()->create();
    $user->assignRole('user');
    $group = Group::factory()->create();

    $response = $this->actingAs($user)->get(route('admin.groups.show', $group));

    $response->assertForbidden();
});

it('returns 403 for regular users attempting to delete a group', function (): void {
    $user = User::factory()->create();
    $user->assignRole('user');
    $group = Group::factory()->create();

    $response = $this->actingAs($user)->delete(route('admin.groups.destroy', $group));

    $response->assertForbidden();
});

// --- Group List ---

it('displays groups with pagination at 25 per page', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    Group::factory()->count(30)->create();

    $response = $this->actingAs($admin)->get(route('admin.groups.index'));

    $response->assertOk();
    $response->assertSee('Next');
});

it('searches groups by name', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    Group::factory()->create(['name' => 'Unique Searchable Group']);
    Group::factory()->create(['name' => 'Other Group']);

    $response = $this->actingAs($admin)->get(route('admin.groups.index', ['search' => 'Unique Searchable']));

    $response->assertOk();
    $response->assertSee('Unique Searchable Group');
    $response->assertDontSee('Other Group');
});

it('searches groups by location', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    Group::factory()->create(['name' => 'Portland Hikers', 'location' => 'Portland, OR']);
    Group::factory()->create(['name' => 'Seattle Runners', 'location' => 'Seattle, WA']);

    $response = $this->actingAs($admin)->get(route('admin.groups.index', ['search' => 'Portland']));

    $response->assertOk();
    $response->assertSee('Portland Hikers');
    $response->assertDontSee('Seattle Runners');
});

it('filters groups by visibility', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    Group::factory()->create(['name' => 'Public Group', 'visibility' => 'public']);
    Group::factory()->create(['name' => 'Private Group', 'visibility' => 'private']);

    $response = $this->actingAs($admin)->get(route('admin.groups.index', ['visibility' => 'private']));

    $response->assertOk();
    $response->assertSee('Private Group');
    $response->assertDontSee('Public Group');
});

// --- Group Detail ---

it('shows group details', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $organizer = User::factory()->create(['name' => 'Group Organizer']);
    $group = Group::factory()->create([
        'name' => 'Detail Test Group',
        'organizer_id' => $organizer->id,
        'location' => 'Denver, CO',
    ]);

    $response = $this->actingAs($admin)->get(route('admin.groups.show', $group));

    $response->assertOk();
    $response->assertSee('Detail Test Group');
    $response->assertSee('Group Organizer');
    $response->assertSee('Denver, CO');
});

// --- Delete ---

it('hard deletes a group', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $group = Group::factory()->create(['name' => 'To Delete']);
    $groupId = $group->id;

    $response = $this->actingAs($admin)->delete(route('admin.groups.destroy', $group));

    $response->assertRedirect(route('admin.groups.index'));

    // Hard delete - should not exist even with trashed
    expect(Group::withTrashed()->find($groupId))->toBeNull();
});
