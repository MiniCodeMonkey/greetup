<?php

use App\Enums\EventType;
use App\Models\Event;
use App\Models\Group;
use App\Models\Rsvp;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);
    $this->withoutVite();
});

it('displays event page with all details for a guest', function (): void {
    $organizer = User::factory()->create(['name' => 'Jane Host']);
    $group = Group::factory()->create(['name' => 'Copenhagen Laravel']);
    $event = Event::factory()->published()->create([
        'group_id' => $group->id,
        'created_by' => $organizer->id,
        'name' => 'March Meetup',
        'description' => 'A great meetup about Laravel.',
        'description_html' => '<p>A great meetup about Laravel.</p>',
        'event_type' => EventType::InPerson,
        'timezone' => 'Europe/Copenhagen',
        'venue_name' => 'Tech Hub',
        'venue_address' => '123 Main St, Copenhagen',
    ]);
    $event->hosts()->attach($organizer->id);

    $response = $this->get(route('events.show', [$group, $event]));

    $response->assertStatus(200)
        ->assertSee('March Meetup')
        ->assertSee('A great meetup about Laravel.')
        ->assertSee('Jane Host')
        ->assertSee('Tech Hub')
        ->assertSee('123 Main St, Copenhagen')
        ->assertSee('Add to Calendar')
        ->assertSee('Share')
        ->assertSee('Details')
        ->assertSee('Attendees')
        ->assertSee('Comments')
        ->assertSee('Chat');
});

it('displays correct SEO title and meta description', function (): void {
    $group = Group::factory()->create(['name' => 'Berlin Tech']);
    $event = Event::factory()->published()->create([
        'group_id' => $group->id,
        'name' => 'Hack Night',
        'description' => 'Come hack with us on exciting projects.',
    ]);

    $response = $this->get(route('events.show', [$group, $event]));

    $response->assertStatus(200)
        ->assertSee('Hack Night · Berlin Tech — Greetup', false)
        ->assertSee('Come hack with us on exciting projects.', false);
});

it('displays time in event timezone', function (): void {
    $group = Group::factory()->create();
    $event = Event::factory()->published()->create([
        'group_id' => $group->id,
        'starts_at' => '2026-03-24 17:30:00',
        'timezone' => 'Europe/Berlin',
    ]);

    $response = $this->get(route('events.show', [$group, $event]));

    $response->assertStatus(200)
        ->assertSee('18:30', false)
        ->assertSee('CET', false);
});

it('shows user timezone when it differs from event timezone', function (): void {
    $user = User::factory()->create(['timezone' => 'America/Los_Angeles']);
    $group = Group::factory()->create();
    $event = Event::factory()->published()->create([
        'group_id' => $group->id,
        'starts_at' => '2026-03-24 17:30:00',
        'timezone' => 'Europe/Berlin',
    ]);

    $response = $this->actingAs($user)
        ->get(route('events.show', [$group, $event]));

    $response->assertStatus(200)
        ->assertSee('your time', false)
        ->assertSee('PDT', false);
});

it('does not show user timezone line when timezones match', function (): void {
    $user = User::factory()->create(['timezone' => 'Europe/Berlin']);
    $group = Group::factory()->create();
    $event = Event::factory()->published()->create([
        'group_id' => $group->id,
        'timezone' => 'Europe/Berlin',
    ]);

    $response = $this->actingAs($user)
        ->get(route('events.show', [$group, $event]));

    $response->assertStatus(200)
        ->assertDontSee('your time');
});

it('shows attendance card with going count and progress bar', function (): void {
    $group = Group::factory()->create();
    $event = Event::factory()->published()->withRsvpLimit(50)->create([
        'group_id' => $group->id,
    ]);

    Rsvp::factory()->going()->count(10)->create(['event_id' => $event->id]);
    Rsvp::factory()->waitlisted()->count(3)->create(['event_id' => $event->id]);

    $response = $this->get(route('events.show', [$group, $event]));

    $response->assertStatus(200)
        ->assertSee('Attendance')
        ->assertSee('10', false)
        ->assertSee('/ 50', false)
        ->assertSee('3 on waitlist');
});

it('shows venue card for in-person events', function (): void {
    $group = Group::factory()->create();
    $event = Event::factory()->published()->create([
        'group_id' => $group->id,
        'event_type' => EventType::InPerson,
        'venue_name' => 'The Grand Hall',
        'venue_address' => '456 Oak Ave',
    ]);

    $response = $this->get(route('events.show', [$group, $event]));

    $response->assertStatus(200)
        ->assertSee('The Grand Hall')
        ->assertSee('456 Oak Ave');
});

it('shows online link for online events', function (): void {
    $group = Group::factory()->create();
    $event = Event::factory()->published()->online()->create([
        'group_id' => $group->id,
        'online_link' => 'https://meet.example.com/event',
    ]);

    $response = $this->get(route('events.show', [$group, $event]));

    $response->assertStatus(200)
        ->assertSee('https://meet.example.com/event')
        ->assertSee('Join online');
});

it('shows both venue and online link for hybrid events', function (): void {
    $group = Group::factory()->create();
    $event = Event::factory()->published()->hybrid()->create([
        'group_id' => $group->id,
        'venue_name' => 'Community Center',
        'online_link' => 'https://zoom.example.com/hybrid',
    ]);

    $response = $this->get(route('events.show', [$group, $event]));

    $response->assertStatus(200)
        ->assertSee('Community Center')
        ->assertSee('https://zoom.example.com/hybrid');
});

it('shows hosts card with host names', function (): void {
    $host1 = User::factory()->create(['name' => 'Alice Host']);
    $host2 = User::factory()->create(['name' => 'Bob Host']);
    $group = Group::factory()->create();
    $event = Event::factory()->published()->create([
        'group_id' => $group->id,
        'created_by' => $host1->id,
    ]);
    $event->hosts()->attach([$host1->id, $host2->id]);

    $response = $this->get(route('events.show', [$group, $event]));

    $response->assertStatus(200)
        ->assertSee('Alice Host')
        ->assertSee('Bob Host')
        ->assertSee('Hosts');
});

it('shows cancellation notice for cancelled events', function (): void {
    $group = Group::factory()->create();
    $event = Event::factory()->cancelled()->create([
        'group_id' => $group->id,
        'cancellation_reason' => 'Weather emergency',
    ]);

    $response = $this->get(route('events.show', [$group, $event]));

    $response->assertStatus(200)
        ->assertSee('This event has been cancelled.')
        ->assertSee('Weather emergency')
        ->assertDontSee('RSVP');
});

it('includes JSON-LD structured data', function (): void {
    $group = Group::factory()->create(['name' => 'Test Group']);
    $event = Event::factory()->published()->create([
        'group_id' => $group->id,
        'name' => 'JSON-LD Test Event',
        'event_type' => EventType::InPerson,
    ]);

    $response = $this->get(route('events.show', [$group, $event]));

    $response->assertStatus(200)
        ->assertSee('application/ld+json', false)
        ->assertSee('"@type":"Event"', false)
        ->assertSee('JSON-LD Test Event', false)
        ->assertSee('OfflineEventAttendanceMode', false)
        ->assertSee('EventScheduled', false);
});

it('includes JSON-LD with EventCancelled status for cancelled events', function (): void {
    $group = Group::factory()->create(['name' => 'Test Group']);
    $event = Event::factory()->cancelled()->create([
        'group_id' => $group->id,
        'name' => 'Cancelled Event',
    ]);

    $response = $this->get(route('events.show', [$group, $event]));

    $response->assertStatus(200)
        ->assertSee('EventCancelled', false);
});

it('includes JSON-LD with OnlineEventAttendanceMode and VirtualLocation for online events', function (): void {
    $group = Group::factory()->create(['name' => 'Online Group']);
    $event = Event::factory()->published()->online()->create([
        'group_id' => $group->id,
        'name' => 'Online Meetup',
        'online_link' => 'https://meet.example.com/room',
    ]);

    $response = $this->get(route('events.show', [$group, $event]));

    $response->assertStatus(200)
        ->assertSee('OnlineEventAttendanceMode', false)
        ->assertSee('VirtualLocation', false)
        ->assertSee('https://meet.example.com/room', false);
});

it('includes JSON-LD with MixedEventAttendanceMode for hybrid events', function (): void {
    $group = Group::factory()->create(['name' => 'Hybrid Group']);
    $event = Event::factory()->published()->hybrid()->create([
        'group_id' => $group->id,
        'name' => 'Hybrid Meetup',
        'venue_name' => 'City Hall',
        'venue_address' => '1 Center St',
        'online_link' => 'https://zoom.example.com/hybrid',
    ]);

    $response = $this->get(route('events.show', [$group, $event]));

    $response->assertStatus(200)
        ->assertSee('MixedEventAttendanceMode', false)
        ->assertSee('Place', false)
        ->assertSee('VirtualLocation', false);
});

it('includes JSON-LD with SoldOut availability when event is full', function (): void {
    $group = Group::factory()->create();
    $event = Event::factory()->published()->withRsvpLimit(2)->create([
        'group_id' => $group->id,
        'name' => 'Full Event',
    ]);
    Rsvp::factory()->going()->count(2)->create(['event_id' => $event->id]);

    $response = $this->get(route('events.show', [$group, $event]));

    $response->assertStatus(200)
        ->assertSee('SoldOut', false);
});

it('includes JSON-LD with PreOrder availability when RSVP is not yet open', function (): void {
    $group = Group::factory()->create();
    $event = Event::factory()->published()->create([
        'group_id' => $group->id,
        'name' => 'Future RSVP Event',
        'rsvp_opens_at' => now()->addWeek(),
    ]);

    $response = $this->get(route('events.show', [$group, $event]));

    $response->assertStatus(200)
        ->assertSee('PreOrder', false);
});

it('includes JSON-LD with InStock availability when spots are available', function (): void {
    $group = Group::factory()->create();
    $event = Event::factory()->published()->withRsvpLimit(50)->create([
        'group_id' => $group->id,
        'name' => 'Open Event',
    ]);
    Rsvp::factory()->going()->count(5)->create(['event_id' => $event->id]);

    $response = $this->get(route('events.show', [$group, $event]));

    $response->assertStatus(200)
        ->assertSee('InStock', false);
});

it('includes JSON-LD with organizer information', function (): void {
    $group = Group::factory()->create(['name' => 'Laravel Copenhagen']);
    $event = Event::factory()->published()->create([
        'group_id' => $group->id,
        'name' => 'Organizer Test',
    ]);

    $response = $this->get(route('events.show', [$group, $event]));

    $response->assertStatus(200)
        ->assertSee('"@type":"Organization"', false)
        ->assertSee('Laravel Copenhagen', false);
});

it('includes JSON-LD with endDate when event has an end time', function (): void {
    $group = Group::factory()->create();
    $event = Event::factory()->published()->create([
        'group_id' => $group->id,
        'starts_at' => '2026-04-01 18:00:00',
        'ends_at' => '2026-04-01 21:00:00',
    ]);

    $response = $this->get(route('events.show', [$group, $event]));

    $response->assertStatus(200)
        ->assertSee('endDate', false)
        ->assertSee('2026-04-01', false);
});

it('includes JSON-LD with free offer details', function (): void {
    $group = Group::factory()->create();
    $event = Event::factory()->published()->create([
        'group_id' => $group->id,
    ]);

    $response = $this->get(route('events.show', [$group, $event]));

    $response->assertStatus(200)
        ->assertSee('"@type":"Offer"', false)
        ->assertSee('"price":"0"', false)
        ->assertSee('"priceCurrency":"USD"', false);
});

it('generates a downloadable .ics file', function (): void {
    $group = Group::factory()->create();
    $event = Event::factory()->published()->create([
        'group_id' => $group->id,
        'name' => 'Calendar Test Event',
        'timezone' => 'UTC',
    ]);

    $response = $this->get(route('events.calendar', [$group, $event]));

    $response->assertStatus(200)
        ->assertHeader('content-type', 'text/calendar; charset=utf-8');

    $content = $response->streamedContent();
    expect($content)->toContain('BEGIN:VCALENDAR')
        ->toContain('Calendar Test Event')
        ->toContain('END:VCALENDAR');
});

it('returns 404 for event belonging to different group', function (): void {
    $group1 = Group::factory()->create();
    $group2 = Group::factory()->create();
    $event = Event::factory()->published()->create([
        'group_id' => $group1->id,
    ]);

    $response = $this->get("/groups/{$group2->slug}/events/{$event->slug}");

    $response->assertStatus(404);
});

it('uses coral accent for in-person events cover band', function (): void {
    $group = Group::factory()->create();
    $event = Event::factory()->published()->create([
        'group_id' => $group->id,
        'event_type' => EventType::InPerson,
    ]);

    $response = $this->get(route('events.show', [$group, $event]));

    $response->assertStatus(200)
        ->assertSee('bg-coral-900', false);
});

it('uses violet accent for online events cover band', function (): void {
    $group = Group::factory()->create();
    $event = Event::factory()->published()->online()->create([
        'group_id' => $group->id,
    ]);

    $response = $this->get(route('events.show', [$group, $event]));

    $response->assertStatus(200)
        ->assertSee('bg-violet-900', false);
});

it('uses green accent for hybrid events cover band', function (): void {
    $group = Group::factory()->create();
    $event = Event::factory()->published()->hybrid()->create([
        'group_id' => $group->id,
    ]);

    $response = $this->get(route('events.show', [$group, $event]));

    $response->assertStatus(200)
        ->assertSee('bg-green-900', false);
});
