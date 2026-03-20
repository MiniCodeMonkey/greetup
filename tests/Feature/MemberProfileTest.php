<?php

use App\Enums\ProfileVisibility;
use App\Models\Block;
use App\Models\Group;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);
});

// --- Public profile page ---

it('displays a public profile page to a guest', function () {
    $member = User::factory()->create([
        'profile_visibility' => ProfileVisibility::Public,
        'bio' => 'I love building things.',
        'location' => 'New York, NY',
        'looking_for' => ['making friends', 'networking'],
    ]);

    $this->get("/members/{$member->id}")
        ->assertOk()
        ->assertSee($member->name)
        ->assertSee('I love building things.')
        ->assertSee('New York, NY')
        ->assertSee('Making friends')
        ->assertSee('Networking');
});

it('displays a public profile page to an authenticated user', function () {
    $member = User::factory()->create([
        'profile_visibility' => ProfileVisibility::Public,
    ]);
    $viewer = User::factory()->create();

    $this->actingAs($viewer)
        ->get("/members/{$member->id}")
        ->assertOk()
        ->assertSee($member->name)
        ->assertSee('Message');
});

it('shows groups in common with the viewer', function () {
    $member = User::factory()->create([
        'profile_visibility' => ProfileVisibility::Public,
    ]);
    $viewer = User::factory()->create();

    $group = Group::factory()->create(['name' => 'Laravel Enthusiasts']);
    $group->members()->attach($member->id, ['role' => 'member', 'joined_at' => now()]);
    $group->members()->attach($viewer->id, ['role' => 'member', 'joined_at' => now()]);

    $this->actingAs($viewer)
        ->get("/members/{$member->id}")
        ->assertOk()
        ->assertSee('Laravel Enthusiasts');
});

it('shows report and block in actions dropdown', function () {
    $member = User::factory()->create([
        'profile_visibility' => ProfileVisibility::Public,
    ]);
    $viewer = User::factory()->create();

    $this->actingAs($viewer)
        ->get("/members/{$member->id}")
        ->assertOk()
        ->assertSee('Report')
        ->assertSee('Block');
});

// --- SEO ---

it('sets correct seo title and meta description from bio', function () {
    $member = User::factory()->create([
        'profile_visibility' => ProfileVisibility::Public,
        'name' => 'Jane Doe',
        'bio' => 'A passionate developer who loves open source.',
    ]);

    $this->get("/members/{$member->id}")
        ->assertOk()
        ->assertSee('Jane Doe — '.config('app.name'), false)
        ->assertSee('A passionate developer who loves open source.', false);
});

it('uses fallback meta description when no bio', function () {
    $member = User::factory()->create([
        'profile_visibility' => ProfileVisibility::Public,
        'name' => 'John Smith',
        'bio' => null,
    ]);

    $this->get("/members/{$member->id}")
        ->assertOk()
        ->assertSee('John Smith is a member of '.config('app.name').'.', false);
});

// --- Members only visibility ---

it('returns 403 for members_only profile when viewer does not share a group', function () {
    $member = User::factory()->create([
        'profile_visibility' => ProfileVisibility::MembersOnly,
    ]);
    $viewer = User::factory()->create();

    $this->actingAs($viewer)
        ->get("/members/{$member->id}")
        ->assertForbidden();
});

it('returns 403 for members_only profile when viewer is a guest', function () {
    $member = User::factory()->create([
        'profile_visibility' => ProfileVisibility::MembersOnly,
    ]);

    $this->get("/members/{$member->id}")
        ->assertForbidden();
});

it('allows members_only profile when viewer shares a group', function () {
    $member = User::factory()->create([
        'profile_visibility' => ProfileVisibility::MembersOnly,
    ]);
    $viewer = User::factory()->create();

    $group = Group::factory()->create();
    $group->members()->attach($member->id, ['role' => 'member', 'joined_at' => now()]);
    $group->members()->attach($viewer->id, ['role' => 'member', 'joined_at' => now()]);

    $this->actingAs($viewer)
        ->get("/members/{$member->id}")
        ->assertOk()
        ->assertSee($member->name);
});

// --- Blocked user ---

it('returns 403 when blocked user tries to view blocker profile', function () {
    $blocker = User::factory()->create([
        'profile_visibility' => ProfileVisibility::Public,
    ]);
    $blocked = User::factory()->create();

    Block::factory()->create([
        'blocker_id' => $blocker->id,
        'blocked_id' => $blocked->id,
    ]);

    $this->actingAs($blocked)
        ->get("/members/{$blocker->id}")
        ->assertForbidden();
});

it('allows owner to view their own profile', function () {
    $user = User::factory()->create([
        'profile_visibility' => ProfileVisibility::MembersOnly,
    ]);

    $this->actingAs($user)
        ->get("/members/{$user->id}")
        ->assertOk()
        ->assertSee($user->name);
});
