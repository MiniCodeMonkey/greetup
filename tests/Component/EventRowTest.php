<?php

use Carbon\Carbon;

function makeRowEvent(array $overrides = []): object
{
    $defaults = [
        'title' => 'Laravel Meetup',
        'event_type' => 'in_person',
        'starts_at' => Carbon::parse('2026-03-24 18:30:00'),
        'capacity' => 100,
        'venue' => 'Pleo HQ',
        'rsvps' => collect([
            (object) ['id' => 1, 'name' => 'Alice Smith'],
            (object) ['id' => 2, 'name' => 'Bob Jones'],
            (object) ['id' => 3, 'name' => 'Carol White'],
        ]),
    ];

    return (object) array_merge($defaults, $overrides);
}

it('renders the date block', function () {
    $event = makeRowEvent();

    $view = $this->blade('<x-event-row :event="$event" />', ['event' => $event]);

    // Date block renders month and day
    $view->assertSee('MAR', false);
    $view->assertSee('24', false);
    $view->assertSee('bg-coral-50', false);
});

it('renders the event title', function () {
    $event = makeRowEvent(['title' => 'Vue.js Workshop']);

    $view = $this->blade('<x-event-row :event="$event" />', ['event' => $event]);

    $view->assertSee('Vue.js Workshop', false);
    $view->assertSee('font-size: 15px', false);
});

it('renders the meta line with day time and venue', function () {
    $event = makeRowEvent([
        'starts_at' => Carbon::parse('2026-03-24 18:30:00'),
        'venue' => 'Pleo HQ',
    ]);

    $view = $this->blade('<x-event-row :event="$event" />', ['event' => $event]);

    $view->assertSee('Tuesday', false);
    $view->assertSee('18:30', false);
    $view->assertSee('Pleo HQ', false);
});

it('renders event type badge and going count', function () {
    $event = makeRowEvent(['event_type' => 'online']);

    $view = $this->blade('<x-event-row :event="$event" />', ['event' => $event]);

    $view->assertSee('Online', false);
    $view->assertSee('bg-violet-50', false);
    $view->assertSee('3 going', false);
});

it('shows almost full badge when near capacity', function () {
    $rsvps = collect(array_map(fn ($i) => (object) ['id' => $i, 'name' => "User $i"], range(1, 80)));
    $event = makeRowEvent(['capacity' => 100, 'rsvps' => $rsvps]);

    $view = $this->blade('<x-event-row :event="$event" />', ['event' => $event]);

    $view->assertSee('Almost full', false);
});

it('does not show almost full badge when below 75 percent', function () {
    $rsvps = collect(array_map(fn ($i) => (object) ['id' => $i, 'name' => "User $i"], range(1, 50)));
    $event = makeRowEvent(['capacity' => 100, 'rsvps' => $rsvps]);

    $view = $this->blade('<x-event-row :event="$event" />', ['event' => $event]);

    $view->assertDontSee('Almost full', false);
});

it('renders secondary RSVP button when near capacity', function () {
    $rsvps = collect(array_map(fn ($i) => (object) ['id' => $i, 'name' => "User $i"], range(1, 80)));
    $event = makeRowEvent(['capacity' => 100, 'rsvps' => $rsvps]);

    $view = $this->blade('<x-event-row :event="$event" />', ['event' => $event]);

    $view->assertSee('RSVP', false);
    $view->assertSee('text-green-500', false);
    $view->assertSee('border: 1.5px solid', false);
});

it('renders primary RSVP button when plenty of spots available', function () {
    $rsvps = collect(array_map(fn ($i) => (object) ['id' => $i, 'name' => "User $i"], range(1, 20)));
    $event = makeRowEvent(['capacity' => 100, 'rsvps' => $rsvps]);

    $view = $this->blade('<x-event-row :event="$event" />', ['event' => $event]);

    $view->assertSee('RSVP', false);
    $view->assertSee('bg-green-500', false);
    $view->assertSee('text-white', false);
});

it('hides RSVP button when show_rsvp is false', function () {
    $event = makeRowEvent();

    $view = $this->blade('<x-event-row :event="$event" :show_rsvp="false" />', ['event' => $event]);

    $view->assertDontSee('RSVP', false);
});

it('renders responsive classes for mobile layout', function () {
    $event = makeRowEvent();

    $view = $this->blade('<x-event-row :event="$event" />', ['event' => $event]);

    $view->assertSee('flex-col', false);
    $view->assertSee('md:flex-row', false);
    $view->assertSee('w-full', false);
    $view->assertSee('md:w-auto', false);
});
