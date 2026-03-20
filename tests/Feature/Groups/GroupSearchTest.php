<?php

use App\Enums\GroupRole;
use App\Enums\GroupVisibility;
use App\Livewire\GroupSearchPage;
use App\Models\Event;
use App\Models\Group;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);
    $this->withoutVite();
});

it('renders the group search page at /groups', function (): void {
    $response = $this->get(route('groups.index'));

    $response->assertStatus(200);
    $response->assertSeeLivewire(GroupSearchPage::class);
});

it('shows correct SEO title', function (): void {
    $response = $this->get(route('groups.index'));

    $response->assertStatus(200)
        ->assertSee('Browse Groups — '.config('app.name'), false);
});

it('displays public active groups', function (): void {
    $group = Group::factory()->create([
        'name' => 'Laravel Copenhagen',
        'visibility' => GroupVisibility::Public,
        'is_active' => true,
    ]);

    Livewire::test(GroupSearchPage::class)
        ->assertSee('Laravel Copenhagen');
});

it('does not display private groups', function (): void {
    Group::factory()->create([
        'name' => 'Secret Society',
        'visibility' => GroupVisibility::Private,
    ]);

    Livewire::test(GroupSearchPage::class)
        ->assertDontSee('Secret Society');
});

it('does not display inactive groups', function (): void {
    Group::factory()->inactive()->create([
        'name' => 'Dead Group',
    ]);

    Livewire::test(GroupSearchPage::class)
        ->assertDontSee('Dead Group');
});

it('searches groups by name', function (): void {
    Group::factory()->create(['name' => 'PHP Developers']);
    Group::factory()->create(['name' => 'Gardening Club']);

    Livewire::test(GroupSearchPage::class)
        ->set('search', 'PHP')
        ->assertSee('PHP Developers')
        ->assertDontSee('Gardening Club');
});

it('searches groups by description', function (): void {
    Group::factory()->create([
        'name' => 'Tech Meetup',
        'description' => 'A group for discussing artificial intelligence',
    ]);
    Group::factory()->create([
        'name' => 'Book Club',
        'description' => 'We read fiction novels together',
    ]);

    Livewire::test(GroupSearchPage::class)
        ->set('search', 'artificial intelligence')
        ->assertSee('Tech Meetup')
        ->assertDontSee('Book Club');
});

it('filters groups by topic', function (): void {
    $techGroup = Group::factory()->create(['name' => 'Tech Talks']);
    $techGroup->syncTagsWithType(['Technology'], 'topic');

    $artGroup = Group::factory()->create(['name' => 'Art Club']);
    $artGroup->syncTagsWithType(['Art'], 'topic');

    Livewire::test(GroupSearchPage::class)
        ->set('topic', 'Technology')
        ->assertSee('Tech Talks')
        ->assertDontSee('Art Club');
});

it('filters groups by location distance', function (): void {
    // Copenhagen group
    $nearGroup = Group::factory()->create([
        'name' => 'Copenhagen Devs',
        'latitude' => 55.6761,
        'longitude' => 12.5683,
    ]);

    // Tokyo group - far away
    $farGroup = Group::factory()->create([
        'name' => 'Tokyo Devs',
        'latitude' => 35.6762,
        'longitude' => 139.6503,
    ]);

    $user = User::factory()->create([
        'latitude' => 55.6761,
        'longitude' => 12.5683,
    ]);
    $user->assignRole('user');

    Livewire::actingAs($user)
        ->test(GroupSearchPage::class)
        ->set('distance', 50)
        ->assertSee('Copenhagen Devs')
        ->assertDontSee('Tokyo Devs');
});

it('sorts groups by newest', function (): void {
    $older = Group::factory()->create([
        'name' => 'Older Group',
        'created_at' => now()->subDays(10),
    ]);
    $newer = Group::factory()->create([
        'name' => 'Newer Group',
        'created_at' => now()->subDay(),
    ]);

    Livewire::test(GroupSearchPage::class)
        ->set('sort', 'newest')
        ->assertSeeInOrder(['Newer Group', 'Older Group']);
});

it('sorts groups by most members', function (): void {
    $smallGroup = Group::factory()->create(['name' => 'Small Group']);
    $bigGroup = Group::factory()->create(['name' => 'Big Group']);

    // Add members to big group
    $members = User::factory()->count(5)->create();
    foreach ($members as $member) {
        $member->assignRole('user');
        $bigGroup->members()->attach($member->id, [
            'role' => GroupRole::Member->value,
            'joined_at' => now(),
        ]);
    }

    Livewire::test(GroupSearchPage::class)
        ->set('sort', 'most_members')
        ->assertSeeInOrder(['Big Group', 'Small Group']);
});

it('sorts groups by most active', function (): void {
    $quietGroup = Group::factory()->create(['name' => 'Quiet Group']);
    $activeGroup = Group::factory()->create(['name' => 'Active Group']);

    // Create recent events for active group
    Event::factory()->count(3)->create([
        'group_id' => $activeGroup->id,
        'starts_at' => now()->addDays(5),
    ]);

    Livewire::test(GroupSearchPage::class)
        ->set('sort', 'most_active')
        ->assertSeeInOrder(['Active Group', 'Quiet Group']);
});

it('displays group member and event counts', function (): void {
    $group = Group::factory()->create(['name' => 'Test Group']);

    $member = User::factory()->create();
    $member->assignRole('user');
    $group->members()->attach($member->id, [
        'role' => GroupRole::Member->value,
        'joined_at' => now(),
    ]);

    Livewire::test(GroupSearchPage::class)
        ->assertSee('1 member');
});

it('paginates with cursor-based infinite scroll at 12 per page', function (): void {
    Group::factory()->count(15)->create();

    Livewire::test(GroupSearchPage::class)
        ->assertSet('perPage', 12)
        ->assertSet('hasMorePages', true)
        ->call('loadMore')
        ->assertSet('page', 2)
        ->assertSet('hasMorePages', false);
});

it('resets pagination when search changes', function (): void {
    Livewire::test(GroupSearchPage::class)
        ->set('page', 3)
        ->set('search', 'test')
        ->assertSet('page', 1);
});

it('resets pagination when filters change', function (): void {
    Livewire::test(GroupSearchPage::class)
        ->set('page', 3)
        ->set('topic', 'Technology')
        ->assertSet('page', 1);
});

it('shows available topics in filter dropdown', function (): void {
    $group = Group::factory()->create();
    $group->syncTagsWithType(['Technology', 'Art'], 'topic');

    Livewire::test(GroupSearchPage::class)
        ->assertSee('Technology')
        ->assertSee('Art');
});

it('shows empty state when no groups match', function (): void {
    Livewire::test(GroupSearchPage::class)
        ->set('search', 'nonexistent group xyz')
        ->assertSee('No groups found');
});
