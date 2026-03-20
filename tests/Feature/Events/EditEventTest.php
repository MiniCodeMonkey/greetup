<?php

use App\Enums\EventStatus;
use App\Enums\EventType;
use App\Enums\GroupRole;
use App\Enums\RsvpStatus;
use App\Models\Event;
use App\Models\Group;
use App\Models\Rsvp;
use App\Models\User;
use App\Notifications\EventUpdated;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);
    $this->withoutVite();
});

function createEditGroupWithOrganizer(GroupRole $role = GroupRole::EventOrganizer): array
{
    $owner = User::factory()->create();
    $group = Group::factory()->create(['organizer_id' => $owner->id, 'timezone' => 'America/New_York']);
    $user = User::factory()->create();
    $group->members()->attach($user->id, ['role' => $role->value, 'joined_at' => now()]);

    return [$user, $group, $owner];
}

function createEditableEvent(Group $group, User $creator, array $overrides = []): Event
{
    return Event::factory()->published()->create(array_merge([
        'group_id' => $group->id,
        'created_by' => $creator->id,
        'starts_at' => now()->addDays(7),
        'ends_at' => now()->addDays(7)->addHours(2),
        'timezone' => 'America/New_York',
        'venue_name' => 'Test Venue',
        'venue_address' => '123 Test St',
    ], $overrides));
}

it('displays the edit form for an event organizer', function (): void {
    [$user, $group] = createEditGroupWithOrganizer();
    $event = createEditableEvent($group, $user);
    $event->hosts()->attach($user->id);

    $this->actingAs($user)
        ->get(route('events.edit', [$group, $event]))
        ->assertStatus(200)
        ->assertSee('Edit Event')
        ->assertSee($event->name);
});

it('displays the edit form for an event host who is a member', function (): void {
    [$organizer, $group] = createEditGroupWithOrganizer();
    $host = User::factory()->create();
    $group->members()->attach($host->id, ['role' => GroupRole::Member->value, 'joined_at' => now()]);

    $event = createEditableEvent($group, $organizer);
    $event->hosts()->attach($host->id);

    $this->actingAs($host)
        ->get(route('events.edit', [$group, $event]))
        ->assertStatus(200)
        ->assertSee('Edit Event');
});

it('denies access to non-hosts with member role', function (): void {
    [$organizer, $group] = createEditGroupWithOrganizer();
    $member = User::factory()->create();
    $group->members()->attach($member->id, ['role' => GroupRole::Member->value, 'joined_at' => now()]);

    $event = createEditableEvent($group, $organizer);

    $this->actingAs($member)
        ->get(route('events.edit', [$group, $event]))
        ->assertStatus(403);
});

it('updates an event with all editable fields', function (): void {
    Queue::fake();

    [$user, $group] = createEditGroupWithOrganizer();
    $event = createEditableEvent($group, $user);
    $event->hosts()->attach($user->id);

    Queue::fake();

    $response = $this->actingAs($user)
        ->put(route('events.update', [$group, $event]), [
            'name' => 'Updated Event Name',
            'description' => '## Updated\n\nNew description.',
            'starts_at' => '2026-07-01 18:00',
            'ends_at' => '2026-07-01 21:00',
            'event_type' => 'online',
            'online_link' => 'https://zoom.us/j/999999',
            'rsvp_limit' => 100,
            'guest_limit' => 3,
            'is_chat_enabled' => true,
            'is_comments_enabled' => false,
            'timezone' => 'America/New_York',
        ]);

    $response->assertRedirect(route('groups.show', $group));

    $event->refresh();

    expect($event->name)->toBe('Updated Event Name');
    expect($event->description)->toBe('## Updated\n\nNew description.');
    expect($event->description_html)->toContain('Updated');
    expect($event->event_type)->toBe(EventType::Online);
    expect($event->online_link)->toBe('https://zoom.us/j/999999');
    expect($event->rsvp_limit)->toBe(100);
    expect($event->guest_limit)->toBe(3);
    expect($event->is_chat_enabled)->toBeTrue();
    expect($event->is_comments_enabled)->toBeFalse();
});

it('re-renders description_html when description is updated', function (): void {
    Queue::fake();

    [$user, $group] = createEditGroupWithOrganizer();
    $event = createEditableEvent($group, $user, ['description' => 'Old text', 'description_html' => '<p>Old text</p>']);
    $event->hosts()->attach($user->id);

    Queue::fake();

    $this->actingAs($user)
        ->put(route('events.update', [$group, $event]), [
            'name' => $event->name,
            'description' => '**Bold new text**',
            'starts_at' => '2026-07-01 18:00',
            'event_type' => 'in_person',
            'venue_name' => 'Venue',
            'venue_address' => '123 St',
            'timezone' => 'America/New_York',
        ]);

    $event->refresh();

    expect($event->description)->toBe('**Bold new text**');
    expect($event->description_html)->toContain('<strong>');
});

it('sends EventUpdated notification to Going and Waitlisted members', function (): void {
    Queue::fake();
    Notification::fake();

    [$user, $group] = createEditGroupWithOrganizer();
    $event = createEditableEvent($group, $user);
    $event->hosts()->attach($user->id);

    $goingUser = User::factory()->create();
    $waitlistedUser = User::factory()->create();
    $notGoingUser = User::factory()->create();

    Rsvp::factory()->create(['event_id' => $event->id, 'user_id' => $goingUser->id, 'status' => RsvpStatus::Going]);
    Rsvp::factory()->create(['event_id' => $event->id, 'user_id' => $waitlistedUser->id, 'status' => RsvpStatus::Waitlisted]);
    Rsvp::factory()->create(['event_id' => $event->id, 'user_id' => $notGoingUser->id, 'status' => RsvpStatus::NotGoing]);

    $this->actingAs($user)
        ->put(route('events.update', [$group, $event]), [
            'name' => 'Updated Name',
            'starts_at' => '2026-07-01 18:00',
            'event_type' => 'in_person',
            'venue_name' => 'Venue',
            'venue_address' => '123 St',
            'timezone' => 'America/New_York',
        ]);

    Notification::assertSentTo($goingUser, EventUpdated::class);
    Notification::assertSentTo($waitlistedUser, EventUpdated::class);
    Notification::assertNotSentTo($notGoingUser, EventUpdated::class);
});

it('does not send notifications for draft events', function (): void {
    Queue::fake();
    Notification::fake();

    [$user, $group] = createEditGroupWithOrganizer();
    $event = createEditableEvent($group, $user, ['status' => EventStatus::Draft]);
    $event->hosts()->attach($user->id);

    $goingUser = User::factory()->create();
    Rsvp::factory()->create(['event_id' => $event->id, 'user_id' => $goingUser->id, 'status' => RsvpStatus::Going]);

    $this->actingAs($user)
        ->put(route('events.update', [$group, $event]), [
            'name' => 'Updated Draft',
            'starts_at' => '2026-07-01 18:00',
            'event_type' => 'in_person',
            'venue_name' => 'Venue',
            'venue_address' => '123 St',
            'timezone' => 'America/New_York',
        ]);

    Notification::assertNotSentTo($goingUser, EventUpdated::class);
});

it('allows editing within 24 hours after ends_at', function (): void {
    Queue::fake();

    [$user, $group] = createEditGroupWithOrganizer();
    $event = createEditableEvent($group, $user, [
        'starts_at' => now()->subHours(5),
        'ends_at' => now()->subHours(2),
    ]);
    $event->hosts()->attach($user->id);

    Queue::fake();

    $this->actingAs($user)
        ->put(route('events.update', [$group, $event]), [
            'name' => 'Post-Event Edit',
            'starts_at' => now()->subHours(5)->format('Y-m-d H:i'),
            'event_type' => 'in_person',
            'venue_name' => 'Venue',
            'venue_address' => '123 St',
            'timezone' => 'America/New_York',
        ])
        ->assertRedirect(route('groups.show', $group));

    $event->refresh();
    expect($event->name)->toBe('Post-Event Edit');
});

it('rejects editing after the 24-hour window (with ends_at)', function (): void {
    Queue::fake();

    [$user, $group] = createEditGroupWithOrganizer();
    $event = createEditableEvent($group, $user, [
        'starts_at' => now()->subDays(3),
        'ends_at' => now()->subDays(2),
    ]);
    $event->hosts()->attach($user->id);

    $this->actingAs($user)
        ->put(route('events.update', [$group, $event]), [
            'name' => 'Too Late Edit',
            'starts_at' => '2026-07-01 18:00',
            'event_type' => 'in_person',
            'venue_name' => 'Venue',
            'venue_address' => '123 St',
            'timezone' => 'America/New_York',
        ])
        ->assertStatus(403);
});

it('rejects editing after the 24-hour window (no ends_at, uses starts_at)', function (): void {
    Queue::fake();

    [$user, $group] = createEditGroupWithOrganizer();
    $event = createEditableEvent($group, $user, [
        'starts_at' => now()->subDays(3),
        'ends_at' => null,
    ]);
    $event->hosts()->attach($user->id);

    $this->actingAs($user)
        ->put(route('events.update', [$group, $event]), [
            'name' => 'Too Late Edit',
            'starts_at' => '2026-07-01 18:00',
            'event_type' => 'in_person',
            'venue_name' => 'Venue',
            'venue_address' => '123 St',
            'timezone' => 'America/New_York',
        ])
        ->assertStatus(403);
});

it('rejects viewing the edit page after the 24-hour window', function (): void {
    [$user, $group] = createEditGroupWithOrganizer();
    $event = createEditableEvent($group, $user, [
        'starts_at' => now()->subDays(3),
        'ends_at' => now()->subDays(2),
    ]);
    $event->hosts()->attach($user->id);

    $this->actingAs($user)
        ->get(route('events.edit', [$group, $event]))
        ->assertStatus(403);
});

it('requires authentication to edit an event', function (): void {
    $group = Group::factory()->create();
    $event = Event::factory()->published()->create([
        'group_id' => $group->id,
        'starts_at' => now()->addDays(7),
    ]);

    $this->get(route('events.edit', [$group, $event]))
        ->assertRedirect(route('login'));
});
