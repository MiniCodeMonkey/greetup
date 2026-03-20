<?php

use App\Enums\EventStatus;
use App\Enums\EventType;
use App\Models\Event;
use App\Models\EventSeries;
use App\Models\Group;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\MediaLibrary\HasMedia;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);
});

it('can be created using the factory', function (): void {
    $event = Event::factory()->create();

    expect($event)->toBeInstanceOf(Event::class)
        ->and($event->exists)->toBeTrue()
        ->and($event->name)->toBeString()
        ->and($event->slug)->toBeString()
        ->and($event->description)->toBeString()
        ->and($event->description_html)->toBeString()
        ->and($event->event_type)->toBe(EventType::InPerson)
        ->and($event->status)->toBe(EventStatus::Draft)
        ->and($event->starts_at)->not->toBeNull()
        ->and($event->timezone)->toBeString()
        ->and($event->is_chat_enabled)->toBeTrue()
        ->and($event->is_comments_enabled)->toBeTrue();
});

it('has group belongsTo relationship', function (): void {
    $event = Event::factory()->create();

    expect($event->group())->toBeInstanceOf(BelongsTo::class)
        ->and($event->group)->toBeInstanceOf(Group::class);
});

it('has creator belongsTo relationship', function (): void {
    $event = Event::factory()->create();

    expect($event->creator())->toBeInstanceOf(BelongsTo::class)
        ->and($event->creator)->toBeInstanceOf(User::class);
});

it('has hosts belongsToMany relationship', function (): void {
    $event = Event::factory()->create();

    expect($event->hosts())->toBeInstanceOf(BelongsToMany::class);
});

it('has rsvps hasMany relationship', function (): void {
    $event = Event::factory()->create();

    expect($event->rsvps())->toBeInstanceOf(HasMany::class);
});

it('has comments hasMany relationship', function (): void {
    $event = Event::factory()->create();

    expect($event->comments())->toBeInstanceOf(HasMany::class);
});

it('has chatMessages hasMany relationship', function (): void {
    $event = Event::factory()->create();

    expect($event->chatMessages())->toBeInstanceOf(HasMany::class);
});

it('has feedback hasMany relationship', function (): void {
    $event = Event::factory()->create();

    expect($event->feedback())->toBeInstanceOf(HasMany::class);
});

it('has series belongsTo relationship', function (): void {
    $series = EventSeries::factory()->create();
    $event = Event::factory()->create(['series_id' => $series->id]);

    expect($event->series())->toBeInstanceOf(BelongsTo::class)
        ->and($event->series)->toBeInstanceOf(EventSeries::class);
});

it('generates slug from name automatically', function (): void {
    $event = Event::factory()->create(['name' => 'My Awesome Event']);

    expect($event->slug)->toBe('my-awesome-event');
});

it('generates unique slugs within the same group', function (): void {
    $group = Group::factory()->create();

    $event1 = Event::factory()->create(['name' => 'Duplicate Name', 'group_id' => $group->id]);
    $event2 = Event::factory()->create(['name' => 'Duplicate Name', 'group_id' => $group->id]);

    expect($event1->slug)->toBe('duplicate-name')
        ->and($event2->slug)->not->toBe($event1->slug)
        ->and($event2->slug)->toStartWith('duplicate-name');
});

it('allows same slug in different groups', function (): void {
    $group1 = Group::factory()->create();
    $group2 = Group::factory()->create();

    $event1 = Event::factory()->create(['name' => 'Same Name', 'group_id' => $group1->id]);
    $event2 = Event::factory()->create(['name' => 'Same Name', 'group_id' => $group2->id]);

    expect($event1->slug)->toBe('same-name')
        ->and($event2->slug)->toBe('same-name');
});

it('casts event_type to EventType enum', function (): void {
    $event = Event::factory()->create();

    expect($event->event_type)->toBeInstanceOf(EventType::class);
});

it('casts status to EventStatus enum', function (): void {
    $event = Event::factory()->create();

    expect($event->status)->toBeInstanceOf(EventStatus::class);
});

it('casts starts_at and ends_at to datetime', function (): void {
    $event = Event::factory()->create(['ends_at' => now()->addHours(2)]);

    expect($event->starts_at)->toBeInstanceOf(Carbon\Carbon::class)
        ->and($event->ends_at)->toBeInstanceOf(Carbon\Carbon::class);
});

it('casts venue_latitude and venue_longitude to decimal', function (): void {
    $event = Event::factory()->create([
        'venue_latitude' => 55.6761000,
        'venue_longitude' => 12.5683000,
    ]);

    expect($event->venue_latitude)->toBe('55.6761000')
        ->and($event->venue_longitude)->toBe('12.5683000');
});

it('casts boolean fields', function (): void {
    $event = Event::factory()->create([
        'is_chat_enabled' => false,
        'is_comments_enabled' => false,
    ]);

    expect($event->is_chat_enabled)->toBeBool()->toBeFalse()
        ->and($event->is_comments_enabled)->toBeBool()->toBeFalse();
});

it('casts rsvp_limit and guest_limit to integer', function (): void {
    $event = Event::factory()->create([
        'rsvp_limit' => 50,
        'guest_limit' => 2,
    ]);

    expect($event->rsvp_limit)->toBeInt()->toBe(50)
        ->and($event->guest_limit)->toBeInt()->toBe(2);
});

it('implements HasMedia interface', function (): void {
    $event = Event::factory()->create();

    expect($event)->toBeInstanceOf(HasMedia::class);
});

it('registers cover_photo media collection', function (): void {
    $event = Event::factory()->create();
    $event->registerMediaCollections();

    $collections = $event->getRegisteredMediaCollections();
    $coverPhoto = $collections->firstWhere('name', 'cover_photo');

    expect($coverPhoto)->not->toBeNull()
        ->and($coverPhoto->singleFile)->toBeTrue();
});

it('registers card and header media conversions', function (): void {
    $event = Event::factory()->create();
    $event->registerMediaConversions();

    $reflection = new ReflectionProperty($event, 'mediaConversions');
    $conversions = $reflection->getValue($event);

    $conversionNames = array_map(fn ($c) => $c->getName(), $conversions);

    expect($conversionNames)->toContain('card')
        ->and($conversionNames)->toContain('header');
});

it('scopes upcoming events', function (): void {
    Event::factory()->published()->create(['starts_at' => now()->addDay()]);
    Event::factory()->published()->create(['starts_at' => now()->subDay()]);
    Event::factory()->draft()->create(['starts_at' => now()->addDay()]);

    $upcoming = Event::upcoming()->get();

    expect($upcoming)->toHaveCount(1);
});

it('scopes past events', function (): void {
    Event::factory()->create(['starts_at' => now()->subDay()]);
    Event::factory()->create(['starts_at' => now()->addDay()]);

    $past = Event::past()->get();

    expect($past)->toHaveCount(1);
});

it('scopes published events', function (): void {
    Event::factory()->published()->create();
    Event::factory()->draft()->create();
    Event::factory()->cancelled()->create();

    $published = Event::published()->get();

    expect($published)->toHaveCount(1)
        ->and($published->first()->status)->toBe(EventStatus::Published);
});

it('scopes cancelled events', function (): void {
    Event::factory()->cancelled()->create();
    Event::factory()->published()->create();

    $cancelled = Event::cancelled()->get();

    expect($cancelled)->toHaveCount(1)
        ->and($cancelled->first()->status)->toBe(EventStatus::Cancelled);
});

it('scopes nearby events using haversine formula', function (): void {
    // Copenhagen venue
    Event::factory()->create([
        'name' => 'Copenhagen Event',
        'venue_latitude' => 55.6761000,
        'venue_longitude' => 12.5683000,
    ]);

    // Berlin venue (~355 km from Copenhagen)
    Event::factory()->create([
        'name' => 'Berlin Event',
        'venue_latitude' => 52.5200000,
        'venue_longitude' => 13.4050000,
    ]);

    // New York venue (~6,000 km from Copenhagen)
    Event::factory()->create([
        'name' => 'New York Event',
        'venue_latitude' => 40.7128000,
        'venue_longitude' => -74.0060000,
    ]);

    // Event with no venue location and no group location
    $noLocationGroup = Group::factory()->create([
        'latitude' => null,
        'longitude' => null,
    ]);
    Event::factory()->create([
        'name' => 'No Location Event',
        'group_id' => $noLocationGroup->id,
        'venue_latitude' => null,
        'venue_longitude' => null,
    ]);

    // 50km radius from Copenhagen
    $nearby50 = Event::nearby(55.6761, 12.5683, 50)->get();
    expect($nearby50)->toHaveCount(1)
        ->and($nearby50->first()->name)->toBe('Copenhagen Event');

    // 400km radius
    $nearby400 = Event::nearby(55.6761, 12.5683, 400)->get();
    expect($nearby400)->toHaveCount(2);

    // 10000km radius — all events with coordinates
    $nearby10000 = Event::nearby(55.6761, 12.5683, 10000)->get();
    expect($nearby10000)->toHaveCount(3);
});

it('returns searchable array with correct fields', function (): void {
    $event = Event::factory()->create([
        'name' => 'Test Search Event',
        'description' => 'A test event description',
        'venue_name' => 'Test Venue',
    ]);

    $searchable = $event->toSearchableArray();

    expect($searchable)->toHaveKeys(['id', 'name', 'description', 'venue_name'])
        ->and($searchable['name'])->toBe('Test Search Event')
        ->and($searchable['description'])->toBe('A test event description')
        ->and($searchable['venue_name'])->toBe('Test Venue');
});

it('has draft factory state', function (): void {
    $event = Event::factory()->draft()->create();

    expect($event->status)->toBe(EventStatus::Draft);
});

it('has published factory state', function (): void {
    $event = Event::factory()->published()->create();

    expect($event->status)->toBe(EventStatus::Published);
});

it('has cancelled factory state', function (): void {
    $event = Event::factory()->cancelled()->create();

    expect($event->status)->toBe(EventStatus::Cancelled)
        ->and($event->cancelled_at)->not->toBeNull()
        ->and($event->cancellation_reason)->toBeString();
});

it('has past factory state', function (): void {
    $event = Event::factory()->past()->create();

    expect($event->status)->toBe(EventStatus::Past)
        ->and($event->starts_at->isPast())->toBeTrue();
});

it('has online factory state', function (): void {
    $event = Event::factory()->online()->create();

    expect($event->event_type)->toBe(EventType::Online)
        ->and($event->online_link)->not->toBeNull()
        ->and($event->venue_name)->toBeNull()
        ->and($event->venue_latitude)->toBeNull();
});

it('has hybrid factory state', function (): void {
    $event = Event::factory()->hybrid()->create();

    expect($event->event_type)->toBe(EventType::Hybrid)
        ->and($event->online_link)->not->toBeNull();
});

it('has withRsvpLimit factory state', function (): void {
    $event = Event::factory()->withRsvpLimit(50)->create();

    expect($event->rsvp_limit)->toBe(50);
});

it('attaches hosts with pivot timestamps', function (): void {
    $event = Event::factory()->create();
    $user = User::factory()->create();

    $event->hosts()->attach($user->id);

    expect($event->hosts)->toHaveCount(1)
        ->and($event->hosts->first()->id)->toBe($user->id);
});
