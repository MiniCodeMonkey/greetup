<?php

use Carbon\Carbon;

it('renders in_person date block with coral accent colors', function () {
    $date = Carbon::parse('2026-03-15');

    $view = $this->blade('<x-date-block :date="$date" event_type="in_person" />', ['date' => $date]);

    $view->assertSee('bg-coral-50', false);
    $view->assertSee('text-coral-500', false);
    $view->assertSee('text-coral-900', false);
    $view->assertSee('MAR', false);
    $view->assertSee('15', false);
});

it('renders online date block with violet accent colors', function () {
    $date = Carbon::parse('2026-07-04');

    $view = $this->blade('<x-date-block :date="$date" event_type="online" />', ['date' => $date]);

    $view->assertSee('bg-violet-50', false);
    $view->assertSee('text-violet-500', false);
    $view->assertSee('text-violet-900', false);
    $view->assertSee('JUL', false);
    $view->assertSee('4', false);
});

it('renders hybrid date block with violet accent colors', function () {
    $date = Carbon::parse('2026-12-25');

    $view = $this->blade('<x-date-block :date="$date" event_type="hybrid" />', ['date' => $date]);

    $view->assertSee('bg-violet-50', false);
    $view->assertSee('text-violet-500', false);
    $view->assertSee('text-violet-900', false);
    $view->assertSee('DEC', false);
    $view->assertSee('25', false);
});

it('displays month abbreviation in uppercase', function () {
    $date = Carbon::parse('2026-01-10');

    $view = $this->blade('<x-date-block :date="$date" />', ['date' => $date]);

    $view->assertSee('JAN', false);
});

it('displays correct day number', function () {
    $date = Carbon::parse('2026-09-03');

    $view = $this->blade('<x-date-block :date="$date" />', ['date' => $date]);

    $view->assertSee('3', false);
    $view->assertSee('SEP', false);
});

it('applies rounded-lg border radius', function () {
    $date = Carbon::parse('2026-06-15');

    $view = $this->blade('<x-date-block :date="$date" />', ['date' => $date]);

    $view->assertSee('rounded-lg', false);
});

it('has 56px width', function () {
    $date = Carbon::parse('2026-06-15');

    $view = $this->blade('<x-date-block :date="$date" />', ['date' => $date]);

    $view->assertSee('width: 56px', false);
});

it('defaults to in_person accent when no event_type provided', function () {
    $date = Carbon::parse('2026-05-20');

    $view = $this->blade('<x-date-block :date="$date" />', ['date' => $date]);

    $view->assertSee('bg-coral-50', false);
    $view->assertSee('text-coral-500', false);
    $view->assertSee('text-coral-900', false);
});
