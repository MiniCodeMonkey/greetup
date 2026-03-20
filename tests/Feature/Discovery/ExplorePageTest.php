<?php

use App\Enums\EventType;
use App\Enums\RsvpStatus;
use App\Livewire\ExplorePage;
use App\Models\Event;
use App\Models\Group;
use App\Models\Rsvp;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);
    $this->withoutVite();
});

function createExploreGroup(array $attributes = []): Group
{
    $organizer = User::factory()->create();

    return Group::factory()->create(array_merge([
        'organizer_id' => $organizer->id,
    ], $attributes));
}

function createExploreEvent(Group $group, array $attributes = []): Event
{
    return Event::factory()->published()->create(array_merge([
        'group_id' => $group->id,
        'created_by' => $group->organizer_id,
    ], $attributes));
}

it('renders the explore page for guests', function (): void {
    $group = createExploreGroup();
    createExploreEvent($group, ['name' => 'Laravel Meetup']);

    $this->get('/explore')
        ->assertOk()
        ->assertSee('Events near')
        ->assertSee('Laravel Meetup');
});

it('renders the explore page for authenticated users', function (): void {
    $user = User::factory()->create();
    $group = createExploreGroup();
    createExploreEvent($group, ['name' => 'PHP Conference']);

    $this->actingAs($user)
        ->get('/explore')
        ->assertOk()
        ->assertSee('PHP Conference');
});

it('renders homepage for guests with correct title', function (): void {
    $this->get('/')
        ->assertOk()
        ->assertSee('Greetup — Find your people', false);
});

it('has correct SEO meta tags', function (): void {
    $this->get('/explore')
        ->assertOk()
        ->assertSee('Explore Events — Greetup', false)
        ->assertSee('Discover local meetups, events, and community groups near you.', false);
});

it('lists upcoming published events', function (): void {
    $group = createExploreGroup();
    $published = createExploreEvent($group, [
        'name' => 'Published Event',
        'starts_at' => now()->addDays(5),
    ]);
    $draft = Event::factory()->draft()->create([
        'group_id' => $group->id,
        'created_by' => $group->organizer_id,
        'name' => 'Draft Event',
        'starts_at' => now()->addDays(5),
    ]);

    Livewire::test(ExplorePage::class)
        ->assertSee('Published Event')
        ->assertDontSee('Draft Event');
});

it('filters events by search term', function (): void {
    $group = createExploreGroup();
    createExploreEvent($group, ['name' => 'Laravel Meetup']);
    createExploreEvent($group, ['name' => 'Python Workshop']);

    Livewire::test(ExplorePage::class)
        ->set('search', 'Laravel')
        ->assertSee('Laravel Meetup')
        ->assertDontSee('Python Workshop');
});

it('filters events by event type', function (): void {
    $group = createExploreGroup();
    createExploreEvent($group, [
        'name' => 'In Person Event',
        'event_type' => EventType::InPerson,
    ]);
    createExploreEvent($group, [
        'name' => 'Online Only Event',
        'event_type' => EventType::Online,
    ]);

    Livewire::test(ExplorePage::class)
        ->set('eventType', 'in_person')
        ->assertSee('In Person Event')
        ->assertDontSee('Online Only Event');
});

it('filters events by date range', function (): void {
    $group = createExploreGroup();
    createExploreEvent($group, [
        'name' => 'Today Event',
        'starts_at' => now()->addHours(2),
    ]);
    createExploreEvent($group, [
        'name' => 'Next Month Event',
        'starts_at' => now()->addMonths(2),
    ]);

    Livewire::test(ExplorePage::class)
        ->set('dateRange', 'today')
        ->assertSee('Today Event')
        ->assertDontSee('Next Month Event');
});

it('shows online events in a separate section', function (): void {
    $group = createExploreGroup();
    createExploreEvent($group, [
        'name' => 'Online Workshop',
        'event_type' => EventType::Online,
        'online_link' => 'https://example.com/meet',
    ]);

    Livewire::test(ExplorePage::class)
        ->assertSee('Online Events')
        ->assertSee('Online Workshop');
});

it('shows popular events for guests sorted by RSVP count', function (): void {
    $group = createExploreGroup();

    $popular = createExploreEvent($group, ['name' => 'Popular Event']);
    $lesserKnown = createExploreEvent($group, ['name' => 'Lesser Known Event']);

    // Create RSVPs for popular event
    for ($i = 0; $i < 5; $i++) {
        Rsvp::create([
            'event_id' => $popular->id,
            'user_id' => User::factory()->create()->id,
            'status' => RsvpStatus::Going,
            'guest_count' => 0,
        ]);
    }

    Livewire::test(ExplorePage::class)
        ->assertSeeInOrder(['Popular Event', 'Lesser Known Event']);
});

it('shows group events first for authenticated users without location', function (): void {
    $user = User::factory()->create(['latitude' => null, 'longitude' => null, 'location' => null]);
    $group = createExploreGroup();
    $group->members()->attach($user->id, ['role' => 'member', 'joined_at' => now()]);

    $groupEvent = createExploreEvent($group, ['name' => 'My Group Event']);

    $otherGroup = createExploreGroup();
    $otherEvent = createExploreEvent($otherGroup, ['name' => 'Other Event']);

    // Add RSVPs to other event to make it popular
    for ($i = 0; $i < 10; $i++) {
        Rsvp::create([
            'event_id' => $otherEvent->id,
            'user_id' => User::factory()->create()->id,
            'status' => RsvpStatus::Going,
            'guest_count' => 0,
        ]);
    }

    Livewire::actingAs($user)
        ->test(ExplorePage::class)
        ->assertSeeInOrder(['My Group Event', 'Other Event']);
});

it('shows location prompt for authenticated users without location', function (): void {
    $user = User::factory()->create(['latitude' => null, 'longitude' => null, 'location' => null]);

    Livewire::actingAs($user)
        ->test(ExplorePage::class)
        ->assertSee('Set your location to see nearby events');
});

it('does not show location prompt for users with location', function (): void {
    $user = User::factory()->create([
        'latitude' => 55.6761,
        'longitude' => 12.5683,
        'location' => 'Copenhagen, Denmark',
    ]);

    Livewire::actingAs($user)
        ->test(ExplorePage::class)
        ->assertDontSee('Set your location to see nearby events');
});

it('shows nearby events for authenticated users with location', function (): void {
    $user = User::factory()->create([
        'latitude' => 55.6761,
        'longitude' => 12.5683,
        'location' => 'Copenhagen, Denmark',
    ]);

    $nearbyGroup = createExploreGroup([
        'latitude' => 55.68,
        'longitude' => 12.57,
        'location' => 'Copenhagen, Denmark',
    ]);
    $nearbyEvent = createExploreEvent($nearbyGroup, [
        'name' => 'Nearby Event',
        'venue_latitude' => 55.68,
        'venue_longitude' => 12.57,
    ]);

    $farGroup = createExploreGroup([
        'latitude' => 35.68,
        'longitude' => 139.65,
        'location' => 'Tokyo, Japan',
    ]);
    $farEvent = createExploreEvent($farGroup, [
        'name' => 'Far Away Event',
        'venue_latitude' => 35.68,
        'venue_longitude' => 139.65,
    ]);

    Livewire::actingAs($user)
        ->test(ExplorePage::class)
        ->assertSee('Nearby Event');
});

it('supports infinite scroll by loading more events', function (): void {
    $group = createExploreGroup();

    for ($i = 1; $i <= 15; $i++) {
        createExploreEvent($group, [
            'name' => "Event $i",
            'starts_at' => now()->addDays($i),
        ]);
    }

    $component = Livewire::test(ExplorePage::class);
    $component->assertSet('page', 1);
    $component->assertSet('hasMorePages', true);

    $component->call('loadMore');
    $component->assertSet('page', 2);
});

it('filters events by topic', function (): void {
    $group1 = createExploreGroup();
    $group1->attachTag('Technology', 'interest');
    createExploreEvent($group1, ['name' => 'Tech Meetup']);

    $group2 = createExploreGroup();
    $group2->attachTag('Sports', 'interest');
    createExploreEvent($group2, ['name' => 'Sports Gathering']);

    Livewire::test(ExplorePage::class)
        ->set('topic', 'Technology')
        ->assertSee('Tech Meetup')
        ->assertDontSee('Sports Gathering');
});
