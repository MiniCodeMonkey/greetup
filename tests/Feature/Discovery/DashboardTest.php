<?php

use App\Enums\RsvpStatus;
use App\Livewire\DashboardPage;
use App\Models\Event;
use App\Models\Group;
use App\Models\Rsvp;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\Notification;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);
    $this->withoutVite();
});

function createDashboardGroup(array $attributes = []): Group
{
    $organizer = User::factory()->create();

    return Group::factory()->create(array_merge([
        'organizer_id' => $organizer->id,
    ], $attributes));
}

function createDashboardEvent(Group $group, array $attributes = []): Event
{
    return Event::factory()->published()->create(array_merge([
        'group_id' => $group->id,
        'created_by' => $group->organizer_id,
    ], $attributes));
}

it('requires authentication', function (): void {
    $this->get('/dashboard')
        ->assertRedirect('/login');
});

it('renders the dashboard for authenticated users', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertOk()
        ->assertSee('Dashboard');
});

it('guests see homepage instead of dashboard', function (): void {
    $this->get('/')
        ->assertOk()
        ->assertSee('Find your people', false);
});

it('authenticated users are redirected from homepage to dashboard', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/')
        ->assertRedirect('/dashboard');
});

it('has correct SEO title', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertOk()
        ->assertSee('Dashboard — Greetup', false);
});

it('shows upcoming events the user RSVP\'d Going to, sorted by date', function (): void {
    $user = User::factory()->create();
    $group = createDashboardGroup();
    $group->members()->attach($user);

    $laterEvent = createDashboardEvent($group, [
        'name' => 'Later Meetup',
        'starts_at' => now()->addDays(10),
    ]);
    $soonerEvent = createDashboardEvent($group, [
        'name' => 'Sooner Meetup',
        'starts_at' => now()->addDays(2),
    ]);

    Rsvp::factory()->create(['user_id' => $user->id, 'event_id' => $laterEvent->id, 'status' => RsvpStatus::Going]);
    Rsvp::factory()->create(['user_id' => $user->id, 'event_id' => $soonerEvent->id, 'status' => RsvpStatus::Going]);

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertOk()
        ->assertSeeInOrder(['Sooner Meetup', 'Later Meetup']);
});

it('does not show events with non-Going RSVP status in upcoming', function (): void {
    $user = User::factory()->create();
    $group = createDashboardGroup();

    $event = createDashboardEvent($group, [
        'name' => 'Not Going Event',
        'starts_at' => now()->addDays(5),
    ]);

    Rsvp::factory()->create(['user_id' => $user->id, 'event_id' => $event->id, 'status' => RsvpStatus::NotGoing]);

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertOk()
        ->assertDontSee('Not Going Event');
});

it('shows empty state for upcoming events when none exist', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertOk()
        ->assertSee('No upcoming events');
});

it('shows the user\'s groups with next event per group', function (): void {
    $user = User::factory()->create();
    $group = createDashboardGroup(['name' => 'PHP Developers']);
    $group->members()->attach($user);

    $nextEvent = createDashboardEvent($group, [
        'name' => 'Next PHP Meetup',
        'starts_at' => now()->addDays(3),
    ]);

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertOk()
        ->assertSee('PHP Developers')
        ->assertSee('Next PHP Meetup');
});

it('shows empty state for groups when user has none', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertOk()
        ->assertSee('You have not joined any groups yet');
});

it('shows suggested events from user\'s groups not yet RSVP\'d', function (): void {
    $user = User::factory()->create();
    $group = createDashboardGroup();
    $group->members()->attach($user);

    $suggestedEvent = createDashboardEvent($group, [
        'name' => 'Suggested Group Event',
        'starts_at' => now()->addDays(5),
    ]);

    // User has NOT RSVP'd to this event
    $this->actingAs($user)
        ->get('/dashboard')
        ->assertOk()
        ->assertSee('Suggested Group Event');
});

it('does not suggest events user already RSVP\'d to', function (): void {
    $user = User::factory()->create();
    $group = createDashboardGroup();
    $group->members()->attach($user);

    $event = createDashboardEvent($group, [
        'name' => 'Already RSVP Event',
        'starts_at' => now()->addDays(5),
    ]);

    Rsvp::factory()->create(['user_id' => $user->id, 'event_id' => $event->id, 'status' => RsvpStatus::Going]);

    $response = $this->actingAs($user)->get('/dashboard');

    // The event should appear in Upcoming Events but not in Suggested Events section
    // We verify by checking Livewire component data
    Livewire::actingAs($user)
        ->test(DashboardPage::class)
        ->assertViewHas('suggestedEvents', function ($suggested) use ($event) {
            return ! $suggested->contains('id', $event->id);
        });
});

it('suggests events from interest-matching groups within location radius', function (): void {
    $user = User::factory()->create([
        'latitude' => 40.7128,
        'longitude' => -74.0060,
    ]);
    $user->attachTags(['PHP'], 'interest');

    $nearbyGroup = createDashboardGroup([
        'latitude' => 40.7300,
        'longitude' => -73.9950,
    ]);
    $nearbyGroup->attachTags(['PHP'], 'interest');

    $event = createDashboardEvent($nearbyGroup, [
        'name' => 'Nearby PHP Event',
        'starts_at' => now()->addDays(5),
        'venue_latitude' => null,
        'venue_longitude' => null,
    ]);

    Livewire::actingAs($user)
        ->test(DashboardPage::class)
        ->assertViewHas('suggestedEvents', function ($suggested) use ($event) {
            return $suggested->contains('id', $event->id);
        });
});

it('orders suggested events by starts_at soonest first', function (): void {
    $user = User::factory()->create();
    $group = createDashboardGroup();
    $group->members()->attach($user);

    createDashboardEvent($group, [
        'name' => 'Later Suggestion',
        'starts_at' => now()->addDays(10),
    ]);
    createDashboardEvent($group, [
        'name' => 'Sooner Suggestion',
        'starts_at' => now()->addDays(2),
    ]);

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertOk()
        ->assertSeeInOrder(['Sooner Suggestion', 'Later Suggestion']);
});

it('shows empty state for suggested events when none available', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertOk()
        ->assertSee('No suggestions yet');
});

it('shows empty state for notifications when none unread', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertOk()
        ->assertSee('No unread notifications');
});

it('shows unread notifications', function (): void {
    $user = User::factory()->create();

    $user->notify(new class extends Notification
    {
        public function via(): array
        {
            return ['database'];
        }

        /** @return array<string, string> */
        public function toDatabase(): array
        {
            return ['message' => 'You have a new RSVP'];
        }
    });

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertOk()
        ->assertSee('You have a new RSVP');
});
