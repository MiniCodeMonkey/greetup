<?php

it('renders fill width proportional to current/max', function () {
    $view = $this->blade('<x-progress-bar :current="15" :max="30" />');

    $view->assertSee('width: 50%', false);
});

it('renders 0% width when current is 0', function () {
    $view = $this->blade('<x-progress-bar :current="0" :max="100" />');

    $view->assertSee('width: 0%', false);
});

it('renders 100% width when current equals max', function () {
    $view = $this->blade('<x-progress-bar :current="50" :max="50" />');

    $view->assertSee('width: 100%', false);
});

it('caps at 100% when current exceeds max', function () {
    $view = $this->blade('<x-progress-bar :current="60" :max="50" />');

    $view->assertSee('width: 100%', false);
});

it('shows coral text when less than 25% spots remaining', function () {
    // 40 of 50 = 10 remaining = 20% remaining → urgent
    $view = $this->blade('<x-progress-bar :current="40" :max="50" />');

    $view->assertSee('text-coral-500', false);
    $view->assertSee('10 spots remaining', false);
});

it('shows neutral text when 25% or more spots remaining', function () {
    // 10 of 50 = 40 remaining = 80% remaining → normal
    $view = $this->blade('<x-progress-bar :current="10" :max="50" />');

    $view->assertSee('text-neutral-500', false);
    $view->assertSee('40 spots remaining', false);
});

it('shows singular spot when exactly 1 remaining', function () {
    $view = $this->blade('<x-progress-bar :current="49" :max="50" />');

    $view->assertSee('1 spot remaining', false);
});

it('renders track with neutral-100 background and rounded-full', function () {
    $view = $this->blade('<x-progress-bar :current="10" :max="20" />');

    $view->assertSee('bg-neutral-100', false);
    $view->assertSee('rounded-full', false);
    $view->assertSee('height: 6px', false);
});

it('renders fill with green-500 background', function () {
    $view = $this->blade('<x-progress-bar :current="10" :max="20" />');

    $view->assertSee('bg-green-500', false);
});

it('renders large current number and smaller max', function () {
    $view = $this->blade('<x-progress-bar :current="25" :max="100" />');

    $view->assertSee('font-size: 24px', false);
    $view->assertSee('font-weight: 500', false);
    $view->assertSee('25', false);
    $view->assertSee('/ 100', false);
});

it('handles null max gracefully with no bar shown', function () {
    $view = $this->blade('<x-progress-bar :current="42" />');

    $view->assertSee('42', false);
    $view->assertDontSee('bg-neutral-100', false);
    $view->assertDontSee('remaining', false);
    $view->assertDontSee('/ ', false);
});

it('renders optional label when provided', function () {
    $view = $this->blade('<x-progress-bar :current="10" :max="50" label="Attendees" />');

    $view->assertSee('Attendees', false);
});
