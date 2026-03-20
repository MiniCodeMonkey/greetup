<?php

use App\Enums\EventStatus;
use App\Enums\GroupVisibility;
use App\Enums\ProfileVisibility;
use App\Livewire\GlobalSearch;
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

function createGlobalSearchGroup(array $attributes = []): Group
{
    $organizer = User::factory()->create();

    return Group::factory()->create(array_merge([
        'organizer_id' => $organizer->id,
    ], $attributes));
}

function createGlobalSearchEvent(Group $group, array $attributes = []): Event
{
    return Event::factory()->published()->create(array_merge([
        'group_id' => $group->id,
        'created_by' => $group->organizer_id,
    ], $attributes));
}

it('renders the search page for guests', function (): void {
    $this->get('/search')
        ->assertOk()
        ->assertSee('Search');
});

it('renders the search page for authenticated users', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/search')
        ->assertOk()
        ->assertSee('Search');
});

it('sets the correct SEO title with query', function (): void {
    $this->get('/search?query=laravel')
        ->assertOk()
        ->assertSee('Search: &quot;laravel&quot;', false);
});

it('searches across groups by name', function (): void {
    $group = createGlobalSearchGroup(['name' => 'Laravel Enthusiasts']);
    createGlobalSearchGroup(['name' => 'Cooking Club']);

    Livewire::test(GlobalSearch::class, ['query' => 'Laravel'])
        ->assertSee('Laravel Enthusiasts')
        ->assertDontSee('Cooking Club');
});

it('searches across groups by description', function (): void {
    $group = createGlobalSearchGroup([
        'name' => 'Tech Group',
        'description' => 'We love building with Laravel framework',
    ]);
    createGlobalSearchGroup([
        'name' => 'Other Group',
        'description' => 'We cook meals together',
    ]);

    Livewire::test(GlobalSearch::class, ['query' => 'Laravel'])
        ->assertSee('Tech Group')
        ->assertDontSee('Other Group');
});

it('searches across events by name', function (): void {
    $group = createGlobalSearchGroup();
    createGlobalSearchEvent($group, ['name' => 'PHP Conference 2026']);
    createGlobalSearchEvent($group, ['name' => 'Yoga Session']);

    Livewire::test(GlobalSearch::class, ['query' => 'PHP Conference'])
        ->assertSee('PHP Conference 2026')
        ->assertDontSee('Yoga Session');
});

it('searches across events by description', function (): void {
    $group = createGlobalSearchGroup();
    createGlobalSearchEvent($group, [
        'name' => 'Tech Talk',
        'description' => 'A deep dive into microservices architecture',
    ]);
    createGlobalSearchEvent($group, [
        'name' => 'Book Club',
        'description' => 'Reading fiction novels together',
    ]);

    Livewire::test(GlobalSearch::class, ['query' => 'microservices'])
        ->assertSee('Tech Talk')
        ->assertDontSee('Book Club');
});

it('searches across users by name with public profiles only', function (): void {
    User::factory()->create([
        'name' => 'Jane Developer',
        'profile_visibility' => ProfileVisibility::Public,
    ]);
    User::factory()->create([
        'name' => 'Jane Private',
        'profile_visibility' => ProfileVisibility::MembersOnly,
    ]);

    Livewire::test(GlobalSearch::class, ['query' => 'Jane'])
        ->assertSee('Jane Developer')
        ->assertDontSee('Jane Private');
});

it('searches across users by bio with public profiles only', function (): void {
    User::factory()->create([
        'name' => 'Public Person',
        'bio' => 'I am a passionate software engineer',
        'profile_visibility' => ProfileVisibility::Public,
    ]);
    User::factory()->create([
        'name' => 'Private Person',
        'bio' => 'I am also a passionate software engineer',
        'profile_visibility' => ProfileVisibility::MembersOnly,
    ]);

    Livewire::test(GlobalSearch::class, ['query' => 'passionate software'])
        ->assertSee('Public Person')
        ->assertDontSee('Private Person');
});

it('returns results grouped by type', function (): void {
    $group = createGlobalSearchGroup(['name' => 'Laravel Community']);
    createGlobalSearchEvent($group, ['name' => 'Laravel Meetup']);
    User::factory()->create([
        'name' => 'Laravel Expert',
        'profile_visibility' => ProfileVisibility::Public,
    ]);

    Livewire::test(GlobalSearch::class, ['query' => 'Laravel'])
        ->assertSeeInOrder(['Groups', 'Laravel Community', 'Events', 'Laravel Meetup', 'Members', 'Laravel Expert']);
});

it('does not show draft events in search results', function (): void {
    $group = createGlobalSearchGroup();
    Event::factory()->create([
        'group_id' => $group->id,
        'created_by' => $group->organizer_id,
        'name' => 'Draft Event',
        'status' => EventStatus::Draft,
    ]);
    createGlobalSearchEvent($group, ['name' => 'Published Event']);

    Livewire::test(GlobalSearch::class, ['query' => 'Event'])
        ->assertSee('Published Event')
        ->assertDontSee('Draft Event');
});

it('does not show private groups in search results', function (): void {
    createGlobalSearchGroup([
        'name' => 'Secret Group',
        'visibility' => GroupVisibility::Private,
    ]);
    createGlobalSearchGroup(['name' => 'Public Group']);

    Livewire::test(GlobalSearch::class, ['query' => 'Group'])
        ->assertSee('Public Group')
        ->assertDontSee('Secret Group');
});

it('shows empty state when no results found', function (): void {
    Livewire::test(GlobalSearch::class, ['query' => 'xyznonexistent'])
        ->assertSee('No results found');
});

it('shows prompt when no query entered', function (): void {
    Livewire::test(GlobalSearch::class)
        ->assertSee('Search for groups, events, and members');
});

it('can search cross-model with a single query', function (): void {
    $group = createGlobalSearchGroup(['name' => 'Photography Lovers']);
    createGlobalSearchEvent($group, ['name' => 'Photography Walk']);
    User::factory()->create([
        'name' => 'Photography Pro',
        'profile_visibility' => ProfileVisibility::Public,
    ]);

    $component = Livewire::test(GlobalSearch::class, ['query' => 'Photography']);

    $component
        ->assertSee('Photography Lovers')
        ->assertSee('Photography Walk')
        ->assertSee('Photography Pro');
});
