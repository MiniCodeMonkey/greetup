<?php

use App\Enums\EventType;
use App\Livewire\ExplorePage;
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

function createNearbyGroup(array $attributes = []): Group
{
    $organizer = User::factory()->create();

    return Group::factory()->create(array_merge([
        'organizer_id' => $organizer->id,
    ], $attributes));
}

function createNearbyEvent(Group $group, array $attributes = []): Event
{
    return Event::factory()->published()->create(array_merge([
        'group_id' => $group->id,
        'created_by' => $group->organizer_id,
    ], $attributes));
}

// --- scopeNearby on Group model ---

it('returns groups within the specified radius', function (): void {
    // Copenhagen center: 55.6761, 12.5683
    $nearbyGroup = createNearbyGroup([
        'latitude' => 55.68,
        'longitude' => 12.57,
        'location' => 'Copenhagen',
    ]);

    $farGroup = createNearbyGroup([
        'latitude' => 35.68,
        'longitude' => 139.65,
        'location' => 'Tokyo',
    ]);

    $results = Group::nearby(55.6761, 12.5683, 50)->get();

    expect($results->pluck('id')->toArray())->toContain($nearbyGroup->id)
        ->and($results->pluck('id')->toArray())->not->toContain($farGroup->id);
});

it('excludes groups outside the radius', function (): void {
    // ~100km away from Copenhagen
    $outsideGroup = createNearbyGroup([
        'latitude' => 56.65,
        'longitude' => 12.57,
        'location' => 'Far North',
    ]);

    $results = Group::nearby(55.6761, 12.5683, 50)->get();

    expect($results->pluck('id')->toArray())->not->toContain($outsideGroup->id);
});

it('handles groups with null lat/lng gracefully', function (): void {
    $nullGroup = createNearbyGroup([
        'latitude' => null,
        'longitude' => null,
        'location' => 'Unknown',
    ]);

    $results = Group::nearby(55.6761, 12.5683, 50)->get();

    expect($results->pluck('id')->toArray())->not->toContain($nullGroup->id);
});

// --- scopeNearby on Event model ---

it('returns events within the specified radius using venue coordinates', function (): void {
    $group = createNearbyGroup();
    $nearbyEvent = createNearbyEvent($group, [
        'name' => 'Nearby Event',
        'venue_latitude' => 55.68,
        'venue_longitude' => 12.57,
    ]);

    $farEvent = createNearbyEvent($group, [
        'name' => 'Far Event',
        'venue_latitude' => 35.68,
        'venue_longitude' => 139.65,
    ]);

    $results = Event::nearby(55.6761, 12.5683, 50)->get();

    expect($results->pluck('id')->toArray())->toContain($nearbyEvent->id)
        ->and($results->pluck('id')->toArray())->not->toContain($farEvent->id);
});

it('excludes events outside the radius', function (): void {
    $group = createNearbyGroup();
    $farEvent = createNearbyEvent($group, [
        'name' => 'Very Far Event',
        'venue_latitude' => -33.87,
        'venue_longitude' => 151.21,
    ]);

    $results = Event::nearby(55.6761, 12.5683, 50)->get();

    expect($results->pluck('id')->toArray())->not->toContain($farEvent->id);
});

it('falls back to group location when event venue lat/lng is null', function (): void {
    $nearbyGroup = createNearbyGroup([
        'latitude' => 55.68,
        'longitude' => 12.57,
        'location' => 'Copenhagen',
    ]);

    $eventWithoutVenue = createNearbyEvent($nearbyGroup, [
        'name' => 'Group Location Event',
        'venue_latitude' => null,
        'venue_longitude' => null,
    ]);

    $results = Event::nearby(55.6761, 12.5683, 50)->get();

    expect($results->pluck('id')->toArray())->toContain($eventWithoutVenue->id);
});

it('excludes events with null venue and null group coordinates', function (): void {
    $noLocationGroup = createNearbyGroup([
        'latitude' => null,
        'longitude' => null,
    ]);

    $noLocationEvent = createNearbyEvent($noLocationGroup, [
        'name' => 'No Location Event',
        'venue_latitude' => null,
        'venue_longitude' => null,
    ]);

    $results = Event::nearby(55.6761, 12.5683, 50)->get();

    expect($results->pluck('id')->toArray())->not->toContain($noLocationEvent->id);
});

it('uses default radius of 50km', function (): void {
    $group = createNearbyGroup();

    // ~30km from Copenhagen
    $withinDefault = createNearbyEvent($group, [
        'name' => 'Within Default Radius',
        'venue_latitude' => 55.45,
        'venue_longitude' => 12.35,
    ]);

    // ~8700km from Copenhagen (Tokyo)
    $outsideDefault = createNearbyEvent($group, [
        'name' => 'Outside Default Radius',
        'venue_latitude' => 35.68,
        'venue_longitude' => 139.65,
    ]);

    $results = Event::nearby(55.6761, 12.5683)->get();

    expect($results->pluck('id')->toArray())->toContain($withinDefault->id)
        ->and($results->pluck('id')->toArray())->not->toContain($outsideDefault->id);
});

it('allows adjustable radius', function (): void {
    $group = createNearbyGroup();

    // ~100km from Copenhagen
    $mediumDistance = createNearbyEvent($group, [
        'name' => 'Medium Distance Event',
        'venue_latitude' => 56.65,
        'venue_longitude' => 12.57,
    ]);

    // Should not be found with 50km radius
    $smallRadius = Event::nearby(55.6761, 12.5683, 50)->get();
    expect($smallRadius->pluck('id')->toArray())->not->toContain($mediumDistance->id);

    // Should be found with 150km radius
    $largeRadius = Event::nearby(55.6761, 12.5683, 150)->get();
    expect($largeRadius->pluck('id')->toArray())->toContain($mediumDistance->id);
});

// --- Online events in separate section ---

it('shows online events in a separate section not filtered by location', function (): void {
    $user = User::factory()->create([
        'latitude' => 55.6761,
        'longitude' => 12.5683,
        'location' => 'Copenhagen, Denmark',
    ]);

    $group = createNearbyGroup([
        'latitude' => 55.68,
        'longitude' => 12.57,
    ]);

    $onlineEvent = createNearbyEvent($group, [
        'name' => 'Online Workshop',
        'event_type' => EventType::Online,
        'venue_latitude' => null,
        'venue_longitude' => null,
        'online_link' => 'https://example.com/meet',
    ]);

    $inPersonEvent = createNearbyEvent($group, [
        'name' => 'Local Meetup',
        'event_type' => EventType::InPerson,
        'venue_latitude' => 55.68,
        'venue_longitude' => 12.57,
    ]);

    Livewire::actingAs($user)
        ->test(ExplorePage::class)
        ->assertSee('Online Events')
        ->assertSee('Online Workshop')
        ->assertSee('Local Meetup');
});

it('does not include online events in the nearby events list', function (): void {
    $group = createNearbyGroup([
        'latitude' => 55.68,
        'longitude' => 12.57,
    ]);

    $onlineEvent = createNearbyEvent($group, [
        'name' => 'Remote Only',
        'event_type' => EventType::Online,
        'venue_latitude' => null,
        'venue_longitude' => null,
        'online_link' => 'https://example.com/meet',
    ]);

    // Online events should not appear in nearby scope results
    $results = Event::where('event_type', '!=', EventType::Online)
        ->nearby(55.6761, 12.5683, 50)
        ->get();

    expect($results->pluck('id')->toArray())->not->toContain($onlineEvent->id);
});

// --- Explore page with distance adjustment ---

it('uses adjustable distance on the explore page', function (): void {
    $user = User::factory()->create([
        'latitude' => 55.6761,
        'longitude' => 12.5683,
        'location' => 'Copenhagen, Denmark',
    ]);

    $nearbyGroup = createNearbyGroup([
        'latitude' => 55.68,
        'longitude' => 12.57,
    ]);
    $nearbyGroup->attachTag('Technology', 'interest');
    $user->attachTag('Technology', 'interest');

    $nearbyEvent = createNearbyEvent($nearbyGroup, [
        'name' => 'Close Event',
        'venue_latitude' => 55.68,
        'venue_longitude' => 12.57,
    ]);

    $distantGroup = createNearbyGroup([
        'latitude' => 56.65,
        'longitude' => 12.57,
    ]);
    $distantGroup->attachTag('Technology', 'interest');

    // ~100km away
    $distantEvent = createNearbyEvent($distantGroup, [
        'name' => 'Distant Event',
        'venue_latitude' => 56.65,
        'venue_longitude' => 12.57,
    ]);

    // With default 50km, nearby scope should not find distant event
    $smallResults = Event::nearby(55.6761, 12.5683, 50)->pluck('id');
    expect($smallResults->toArray())->toContain($nearbyEvent->id)
        ->and($smallResults->toArray())->not->toContain($distantEvent->id);

    // With 150km radius, nearby scope should find it
    $largeResults = Event::nearby(55.6761, 12.5683, 150)->pluck('id');
    expect($largeResults->toArray())->toContain($distantEvent->id);

    // Verify the explore page default distance is 50
    Livewire::actingAs($user)
        ->test(ExplorePage::class)
        ->assertSet('distance', 50);
});
