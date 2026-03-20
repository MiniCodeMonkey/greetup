<?php

use App\Enums\EventStatus;
use App\Enums\EventType;
use App\Enums\GroupRole;
use App\Jobs\GeocodeLocation;
use App\Models\Event;
use App\Models\Group;
use App\Models\User;
use App\Notifications\NewEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);
    $this->withoutVite();
});

function createGroupWithMember(GroupRole $role = GroupRole::EventOrganizer): array
{
    $organizer = User::factory()->create();
    $group = Group::factory()->create(['organizer_id' => $organizer->id, 'timezone' => 'America/New_York']);
    $user = User::factory()->create();
    $group->members()->attach($user->id, ['role' => $role->value, 'joined_at' => now()]);

    return [$user, $group, $organizer];
}

it('displays the event creation form', function (): void {
    [$user, $group] = createGroupWithMember();

    $this->actingAs($user)
        ->get(route('events.create', $group))
        ->assertStatus(200)
        ->assertSee('Create an Event')
        ->assertSee($group->name);
});

it('requires authentication to view the creation form', function (): void {
    $group = Group::factory()->create();

    $this->get(route('events.create', $group))
        ->assertRedirect(route('login'));
});

it('requires event_organizer role to view the creation form', function (): void {
    [$user, $group] = createGroupWithMember(GroupRole::Member);

    $this->actingAs($user)
        ->get(route('events.create', $group))
        ->assertStatus(403);
});

it('creates an event with all fields on happy path', function (): void {
    Queue::fake();

    [$user, $group] = createGroupWithMember();

    $response = $this->actingAs($user)
        ->post(route('events.store', $group), [
            'name' => 'Monthly Laravel Meetup',
            'description' => '## Welcome\n\nJoin us for **Laravel** discussions.',
            'starts_at' => '2026-06-15 18:00',
            'ends_at' => '2026-06-15 21:00',
            'event_type' => 'in_person',
            'venue_name' => 'Copenhagen Convention Center',
            'venue_address' => '123 Main St, Copenhagen, Denmark',
            'rsvp_limit' => 50,
            'guest_limit' => 2,
            'is_chat_enabled' => true,
            'is_comments_enabled' => true,
            'timezone' => 'America/New_York',
            'status' => 'draft',
        ]);

    $event = Event::where('name', 'Monthly Laravel Meetup')->first();
    expect($event)->not->toBeNull();

    $response->assertRedirect(route('groups.show', $group));

    expect($event->group_id)->toBe($group->id);
    expect($event->created_by)->toBe($user->id);
    expect($event->description)->toBe('## Welcome\n\nJoin us for **Laravel** discussions.');
    expect($event->description_html)->toContain('Welcome');
    expect($event->event_type)->toBe(EventType::InPerson);
    expect($event->status)->toBe(EventStatus::Draft);
    expect($event->venue_name)->toBe('Copenhagen Convention Center');
    expect($event->venue_address)->toBe('123 Main St, Copenhagen, Denmark');
    expect($event->rsvp_limit)->toBe(50);
    expect($event->guest_limit)->toBe(2);
    expect($event->is_chat_enabled)->toBeTrue();
    expect($event->is_comments_enabled)->toBeTrue();
    expect($event->timezone)->toBe('America/New_York');

    // Verify slug auto-generated
    expect($event->slug)->toBe('monthly-laravel-meetup');

    // Verify creator is auto-assigned as host
    expect($event->hosts()->where('user_id', $user->id)->exists())->toBeTrue();

    // Verify geocoding job dispatched for venue
    Queue::assertPushed(GeocodeLocation::class, function (GeocodeLocation $job) use ($event) {
        return $job->model->is($event);
    });
});

it('saves event as draft without sending notifications', function (): void {
    Queue::fake();
    Notification::fake();

    [$user, $group, $organizer] = createGroupWithMember();
    $group->members()->attach($organizer->id, ['role' => GroupRole::Organizer->value, 'joined_at' => now()]);

    $this->actingAs($user)
        ->post(route('events.store', $group), [
            'name' => 'Draft Event',
            'starts_at' => '2026-06-15 18:00',
            'event_type' => 'online',
            'online_link' => 'https://zoom.us/j/123456789',
            'status' => 'draft',
        ]);

    $event = Event::where('name', 'Draft Event')->first();
    expect($event)->not->toBeNull();
    expect($event->status)->toBe(EventStatus::Draft);

    Notification::assertNothingSent();
});

it('publishes event and sends NewEvent notification to group members', function (): void {
    Queue::fake();
    Notification::fake();

    [$user, $group, $organizer] = createGroupWithMember();
    $group->members()->attach($organizer->id, ['role' => GroupRole::Organizer->value, 'joined_at' => now()]);

    $member = User::factory()->create();
    $group->members()->attach($member->id, ['role' => GroupRole::Member->value, 'joined_at' => now()]);

    $this->actingAs($user)
        ->post(route('events.store', $group), [
            'name' => 'Published Event',
            'starts_at' => '2026-06-15 18:00',
            'event_type' => 'online',
            'online_link' => 'https://zoom.us/j/123456789',
            'status' => 'published',
        ]);

    $event = Event::where('name', 'Published Event')->first();
    expect($event)->not->toBeNull();
    expect($event->status)->toBe(EventStatus::Published);

    // Notification sent to other members but not the creator
    Notification::assertSentTo($member, NewEvent::class);
    Notification::assertSentTo($organizer, NewEvent::class);
    Notification::assertNotSentTo($user, NewEvent::class);
});

it('validates required fields', function (): void {
    [$user, $group] = createGroupWithMember();

    $this->actingAs($user)
        ->post(route('events.store', $group), [])
        ->assertSessionHasErrors(['name', 'starts_at', 'event_type']);
});

it('validates invalid event_type', function (): void {
    [$user, $group] = createGroupWithMember();

    $this->actingAs($user)
        ->post(route('events.store', $group), [
            'name' => 'Test Event',
            'starts_at' => '2026-06-15 18:00',
            'event_type' => 'invalid_type',
        ])
        ->assertSessionHasErrors(['event_type']);
});

it('requires venue fields for in_person events', function (): void {
    [$user, $group] = createGroupWithMember();

    $this->actingAs($user)
        ->post(route('events.store', $group), [
            'name' => 'In Person Event',
            'starts_at' => '2026-06-15 18:00',
            'event_type' => 'in_person',
        ])
        ->assertSessionHasErrors(['venue_name', 'venue_address']);
});

it('requires online_link for online events', function (): void {
    [$user, $group] = createGroupWithMember();

    $this->actingAs($user)
        ->post(route('events.store', $group), [
            'name' => 'Online Event',
            'starts_at' => '2026-06-15 18:00',
            'event_type' => 'online',
        ])
        ->assertSessionHasErrors(['online_link']);
});

it('requires venue and online_link for hybrid events', function (): void {
    [$user, $group] = createGroupWithMember();

    $this->actingAs($user)
        ->post(route('events.store', $group), [
            'name' => 'Hybrid Event',
            'starts_at' => '2026-06-15 18:00',
            'event_type' => 'hybrid',
        ])
        ->assertSessionHasErrors(['venue_name', 'venue_address', 'online_link']);
});

it('dispatches geocoding job for venue address', function (): void {
    Queue::fake();

    [$user, $group] = createGroupWithMember();

    $this->actingAs($user)
        ->post(route('events.store', $group), [
            'name' => 'Geocoded Event',
            'starts_at' => '2026-06-15 18:00',
            'event_type' => 'in_person',
            'venue_name' => 'Test Venue',
            'venue_address' => '456 Oak Ave, NYC',
            'status' => 'draft',
        ]);

    $event = Event::where('name', 'Geocoded Event')->first();
    expect($event)->not->toBeNull();

    Queue::assertPushed(GeocodeLocation::class, function (GeocodeLocation $job) use ($event) {
        return $job->model->is($event);
    });
});

it('inherits timezone from group by default', function (): void {
    Queue::fake();

    [$user, $group] = createGroupWithMember();

    $this->actingAs($user)
        ->post(route('events.store', $group), [
            'name' => 'Timezone Test Event',
            'starts_at' => '2026-06-15 18:00',
            'event_type' => 'online',
            'online_link' => 'https://zoom.us/j/123',
            'status' => 'draft',
        ]);

    $event = Event::where('name', 'Timezone Test Event')->first();
    expect($event)->not->toBeNull();
    expect($event->timezone)->toBe('America/New_York');
});

it('allows overriding timezone per event', function (): void {
    Queue::fake();

    [$user, $group] = createGroupWithMember();

    $this->actingAs($user)
        ->post(route('events.store', $group), [
            'name' => 'Custom Timezone Event',
            'starts_at' => '2026-06-15 18:00',
            'event_type' => 'online',
            'online_link' => 'https://zoom.us/j/123',
            'timezone' => 'Europe/Berlin',
            'status' => 'draft',
        ]);

    $event = Event::where('name', 'Custom Timezone Event')->first();
    expect($event)->not->toBeNull();
    expect($event->timezone)->toBe('Europe/Berlin');
});

it('converts datetime to UTC for storage', function (): void {
    Queue::fake();

    [$user, $group] = createGroupWithMember();

    $this->actingAs($user)
        ->post(route('events.store', $group), [
            'name' => 'UTC Conversion Event',
            'starts_at' => '2026-06-15 18:00',
            'event_type' => 'online',
            'online_link' => 'https://zoom.us/j/123',
            'timezone' => 'America/New_York',
            'status' => 'draft',
        ]);

    $event = Event::where('name', 'UTC Conversion Event')->first();
    expect($event)->not->toBeNull();
    // 18:00 EDT (UTC-4 in June) = 22:00 UTC
    expect($event->starts_at->format('H:i'))->toBe('22:00');
});

it('handles slug collisions within the same group', function (): void {
    Queue::fake();

    [$user, $group] = createGroupWithMember();

    $this->actingAs($user)
        ->post(route('events.store', $group), [
            'name' => 'Unique Event',
            'starts_at' => '2026-06-15 18:00',
            'event_type' => 'online',
            'online_link' => 'https://zoom.us/j/123',
            'status' => 'draft',
        ]);

    $this->actingAs($user)
        ->post(route('events.store', $group), [
            'name' => 'Unique Event',
            'starts_at' => '2026-07-15 18:00',
            'event_type' => 'online',
            'online_link' => 'https://zoom.us/j/456',
            'status' => 'draft',
        ]);

    $events = Event::where('name', 'Unique Event')->where('group_id', $group->id)->get();
    expect($events)->toHaveCount(2);

    $slugs = $events->pluck('slug')->toArray();
    expect($slugs[0])->not->toBe($slugs[1]);
    expect($slugs)->toContain('unique-event');
});

it('renders description markdown to html', function (): void {
    Queue::fake();

    [$user, $group] = createGroupWithMember();

    $this->actingAs($user)
        ->post(route('events.store', $group), [
            'name' => 'Markdown Event',
            'description' => '**Bold text** and [a link](https://example.com)',
            'starts_at' => '2026-06-15 18:00',
            'event_type' => 'online',
            'online_link' => 'https://zoom.us/j/123',
            'status' => 'draft',
        ]);

    $event = Event::where('name', 'Markdown Event')->first();
    expect($event)->not->toBeNull();
    expect($event->description_html)->toContain('<strong>Bold text</strong>');
    expect($event->description_html)->toContain('a link');
});

it('prevents members without event_organizer role from creating events', function (): void {
    [$user, $group] = createGroupWithMember(GroupRole::Member);

    $this->actingAs($user)
        ->post(route('events.store', $group), [
            'name' => 'Unauthorized Event',
            'starts_at' => '2026-06-15 18:00',
            'event_type' => 'online',
            'online_link' => 'https://zoom.us/j/123',
        ])
        ->assertStatus(403);
});

it('allows co_organizer to create events', function (): void {
    Queue::fake();

    [$user, $group] = createGroupWithMember(GroupRole::CoOrganizer);

    $this->actingAs($user)
        ->post(route('events.store', $group), [
            'name' => 'CoOrg Event',
            'starts_at' => '2026-06-15 18:00',
            'event_type' => 'online',
            'online_link' => 'https://zoom.us/j/123',
            'status' => 'draft',
        ])
        ->assertRedirect(route('groups.show', $group));

    expect(Event::where('name', 'CoOrg Event')->exists())->toBeTrue();
});
