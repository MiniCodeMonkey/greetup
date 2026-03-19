<?php

use Carbon\Carbon;

function makeEvent(array $overrides = []): object
{
    $defaults = [
        'title' => 'Laravel Meetup',
        'event_type' => 'in_person',
        'starts_at' => Carbon::parse('2026-03-24 18:30:00'),
        'capacity' => 100,
        'url' => '/groups/laravel-copenhagen/events/laravel-meetup',
        'group' => (object) ['name' => 'Laravel Copenhagen'],
        'rsvps' => collect([
            (object) ['id' => 1, 'name' => 'Alice Smith'],
            (object) ['id' => 2, 'name' => 'Bob Jones'],
            (object) ['id' => 3, 'name' => 'Carol White'],
        ]),
    ];

    return (object) array_merge($defaults, $overrides);
}

it('renders coral header for in_person events', function () {
    $event = makeEvent(['event_type' => 'in_person']);

    $view = $this->blade('<x-event-card :event="$event" />', ['event' => $event]);

    $view->assertSee('bg-coral-900', false);
});

it('renders violet header for online events', function () {
    $event = makeEvent(['event_type' => 'online']);

    $view = $this->blade('<x-event-card :event="$event" />', ['event' => $event]);

    $view->assertSee('bg-violet-900', false);
});

it('renders green header for hybrid events', function () {
    $event = makeEvent(['event_type' => 'hybrid']);

    $view = $this->blade('<x-event-card :event="$event" />', ['event' => $event]);

    $view->assertSee('bg-green-900', false);
});

it('shows almost full badge when capacity is 75% or more filled', function () {
    $rsvps = collect(array_map(fn ($i) => (object) ['id' => $i, 'name' => "User $i"], range(1, 75)));
    $event = makeEvent(['capacity' => 100, 'rsvps' => $rsvps]);

    $view = $this->blade('<x-event-card :event="$event" />', ['event' => $event]);

    $view->assertSee('Almost full', false);
});

it('does not show almost full badge when below 75% capacity', function () {
    $rsvps = collect(array_map(fn ($i) => (object) ['id' => $i, 'name' => "User $i"], range(1, 50)));
    $event = makeEvent(['capacity' => 100, 'rsvps' => $rsvps]);

    $view = $this->blade('<x-event-card :event="$event" />', ['event' => $event]);

    $view->assertDontSee('Almost full', false);
});

it('renders avatar stack with going count', function () {
    $event = makeEvent();

    $view = $this->blade('<x-event-card :event="$event" />', ['event' => $event]);

    $view->assertSee('3 going', false);
    $view->assertSee('AS', false); // Alice Smith initials
});

it('renders decorative blob on header', function () {
    $event = makeEvent();

    $view = $this->blade('<x-event-card :event="$event" />', ['event' => $event]);

    $view->assertSee('opacity: 0.15', false);
    $view->assertSee('<svg', false);
});

it('links to the event page url', function () {
    $event = makeEvent(['url' => '/groups/laravel-copenhagen/events/laravel-meetup']);

    $view = $this->blade('<x-event-card :event="$event" />', ['event' => $event]);

    $view->assertSee('href="/groups/laravel-copenhagen/events/laravel-meetup"', false);
});

it('renders event title group name and date', function () {
    $event = makeEvent([
        'title' => 'Vue.js Workshop',
        'group' => (object) ['name' => 'Frontend Guild'],
        'starts_at' => Carbon::parse('2026-04-15 19:00:00'),
    ]);

    $view = $this->blade('<x-event-card :event="$event" />', ['event' => $event]);

    $view->assertSee('Vue.js Workshop', false);
    $view->assertSee('Frontend Guild', false);
    $view->assertSee('Wed, Apr 15', false);
});

it('shows spots left in coral when limited', function () {
    $rsvps = collect(array_map(fn ($i) => (object) ['id' => $i, 'name' => "User $i"], range(1, 90)));
    $event = makeEvent(['capacity' => 100, 'rsvps' => $rsvps]);

    $view = $this->blade('<x-event-card :event="$event" />', ['event' => $event]);

    $view->assertSee('10 left', false);
    $view->assertSee('text-coral-500', false);
});

it('shows spots left in neutral when not limited', function () {
    $rsvps = collect(array_map(fn ($i) => (object) ['id' => $i, 'name' => "User $i"], range(1, 30)));
    $event = makeEvent(['capacity' => 100, 'rsvps' => $rsvps]);

    $view = $this->blade('<x-event-card :event="$event" />', ['event' => $event]);

    $view->assertSee('70 left', false);
    $view->assertSee('text-neutral-500', false);
});

it('renders event type pill with white text on translucent background', function () {
    $event = makeEvent(['event_type' => 'online']);

    $view = $this->blade('<x-event-card :event="$event" />', ['event' => $event]);

    $view->assertSee('rgba(255,255,255,0.15)', false);
    $view->assertSee('text-white', false);
    $view->assertSee('Online', false);
});
