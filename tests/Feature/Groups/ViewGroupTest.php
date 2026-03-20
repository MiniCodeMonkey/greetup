<?php

use App\Enums\GroupRole;
use App\Enums\GroupVisibility;
use App\Models\Group;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);
    $this->withoutVite();
});

it('displays a public group profile page to a guest', function (): void {
    $organizer = User::factory()->create(['name' => 'Jane Organizer']);
    $group = Group::factory()->create([
        'name' => 'Copenhagen Laravel Meetup',
        'description' => 'A great group for Laravel enthusiasts in Copenhagen.',
        'description_html' => '<p>A great group for Laravel enthusiasts in Copenhagen.</p>',
        'location' => 'Copenhagen, Denmark',
        'organizer_id' => $organizer->id,
        'visibility' => GroupVisibility::Public,
    ]);

    $group->members()->attach($organizer->id, [
        'role' => GroupRole::Organizer->value,
        'joined_at' => now(),
    ]);

    $response = $this->get(route('groups.show', $group));

    $response->assertStatus(200)
        ->assertSee('Copenhagen Laravel Meetup')
        ->assertSee('Copenhagen, Denmark')
        ->assertSee('A great group for Laravel enthusiasts in Copenhagen.')
        ->assertSee('Jane Organizer')
        ->assertSee('1 member')
        ->assertSee('Join Group')
        ->assertSee('Upcoming Events')
        ->assertSee('Past Events')
        ->assertSee('Discussions')
        ->assertSee('Members')
        ->assertSee('About');
});

it('shows correct SEO title and meta description', function (): void {
    $group = Group::factory()->create([
        'name' => 'Berlin Tech Meetup',
        'description' => 'A vibrant community for tech enthusiasts in Berlin. We meet every week to discuss the latest trends in technology.',
        'visibility' => GroupVisibility::Public,
    ]);

    $response = $this->get(route('groups.show', $group));

    $response->assertStatus(200)
        ->assertSee('Berlin Tech Meetup — Greetup', false);
});

it('displays interest pills with cycling colors', function (): void {
    $group = Group::factory()->create([
        'visibility' => GroupVisibility::Public,
    ]);

    $group->syncTagsWithType(['Laravel', 'PHP', 'Web Dev'], 'topic');

    $response = $this->get(route('groups.show', $group));

    $response->assertStatus(200)
        ->assertSee('Laravel')
        ->assertSee('PHP')
        ->assertSee('Web Dev');
});

it('shows decorative blob header when no cover photo', function (): void {
    $group = Group::factory()->create([
        'visibility' => GroupVisibility::Public,
    ]);

    $response = $this->get(route('groups.show', $group));

    $response->assertStatus(200)
        ->assertSee('bg-green-900', false);
});

it('displays join group button for non-members', function (): void {
    $group = Group::factory()->create([
        'visibility' => GroupVisibility::Public,
        'requires_approval' => false,
    ]);

    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->get(route('groups.show', $group));

    $response->assertStatus(200)
        ->assertSee('Join Group');
});

it('displays request to join button when approval is required', function (): void {
    $group = Group::factory()->create([
        'visibility' => GroupVisibility::Public,
        'requires_approval' => true,
    ]);

    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->get(route('groups.show', $group));

    $response->assertStatus(200)
        ->assertSee('Request to Join');
});

it('shows leave group option for members', function (): void {
    $group = Group::factory()->create([
        'visibility' => GroupVisibility::Public,
    ]);

    $member = User::factory()->create();
    $group->members()->attach($member->id, [
        'role' => GroupRole::Member->value,
        'joined_at' => now(),
    ]);

    $response = $this->actingAs($member)
        ->get(route('groups.show', $group));

    $response->assertStatus(200)
        ->assertSee('Leave Group');
});

it('displays member avatar stack', function (): void {
    $group = Group::factory()->create([
        'visibility' => GroupVisibility::Public,
    ]);

    $members = User::factory()->count(3)->create();
    foreach ($members as $member) {
        $group->members()->attach($member->id, [
            'role' => GroupRole::Member->value,
            'joined_at' => now(),
        ]);
    }

    $response = $this->get(route('groups.show', $group));

    $response->assertStatus(200)
        ->assertSee('3 members');
});

it('restricts private group content for non-members', function (): void {
    $organizer = User::factory()->create();
    $group = Group::factory()->create([
        'name' => 'Secret Society',
        'description' => 'Private group description',
        'description_html' => '<p>Private group description</p>',
        'visibility' => GroupVisibility::Private,
        'organizer_id' => $organizer->id,
    ]);

    $group->members()->attach($organizer->id, [
        'role' => GroupRole::Organizer->value,
        'joined_at' => now(),
    ]);

    $visitor = User::factory()->create();

    $response = $this->actingAs($visitor)
        ->get(route('groups.show', $group));

    $response->assertStatus(200)
        ->assertSee('Secret Society')
        ->assertSee('1 member')
        ->assertSee('This is a private group')
        ->assertDontSee('Upcoming Events')
        ->assertDontSee('Past Events');
});

it('shows full content to private group members', function (): void {
    $group = Group::factory()->create([
        'visibility' => GroupVisibility::Private,
    ]);

    $member = User::factory()->create();
    $group->members()->attach($member->id, [
        'role' => GroupRole::Member->value,
        'joined_at' => now(),
    ]);

    $response = $this->actingAs($member)
        ->get(route('groups.show', $group));

    $response->assertStatus(200)
        ->assertSee('Upcoming Events')
        ->assertSee('Past Events')
        ->assertSee('Discussions')
        ->assertSee('Members')
        ->assertSee('About');
});

it('displays leadership team in about tab', function (): void {
    $organizer = User::factory()->create(['name' => 'Lead Person']);
    $coOrganizer = User::factory()->create(['name' => 'Co-Lead Person']);

    $group = Group::factory()->create([
        'visibility' => GroupVisibility::Public,
        'organizer_id' => $organizer->id,
    ]);

    $group->members()->attach($organizer->id, [
        'role' => GroupRole::Organizer->value,
        'joined_at' => now(),
    ]);
    $group->members()->attach($coOrganizer->id, [
        'role' => GroupRole::CoOrganizer->value,
        'joined_at' => now(),
    ]);

    $response = $this->get(route('groups.show', ['group' => $group->slug, 'tab' => 'about']));

    $response->assertStatus(200)
        ->assertSee('Leadership Team')
        ->assertSee('Lead Person')
        ->assertSee('Co-Lead Person');
});

it('displays members tab with member list', function (): void {
    $group = Group::factory()->create([
        'visibility' => GroupVisibility::Public,
    ]);

    $member = User::factory()->create(['name' => 'Alice Member']);
    $group->members()->attach($member->id, [
        'role' => GroupRole::Member->value,
        'joined_at' => now(),
    ]);

    $response = $this->get(route('groups.show', ['group' => $group->slug, 'tab' => 'members']));

    $response->assertStatus(200)
        ->assertSee('Alice Member');
});

it('restricts private group tabs for guests', function (): void {
    $group = Group::factory()->create([
        'name' => 'Private Club',
        'visibility' => GroupVisibility::Private,
    ]);

    $response = $this->get(route('groups.show', $group));

    $response->assertStatus(200)
        ->assertSee('Private Club')
        ->assertSee('This is a private group')
        ->assertDontSee('Upcoming Events');
});
