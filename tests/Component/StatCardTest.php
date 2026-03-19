<?php

it('renders coral stat card with correct background', function () {
    $view = $this->blade('<x-stat-card :value="42" label="Events" color="coral" />');

    $view->assertSee('bg-coral-500', false);
    $view->assertSee('text-white', false);
    $view->assertSee('42', false);
    $view->assertSee('Events', false);
});

it('renders violet stat card with correct background', function () {
    $view = $this->blade('<x-stat-card :value="128" label="Members" color="violet" />');

    $view->assertSee('bg-violet-500', false);
    $view->assertSee('text-white', false);
    $view->assertSee('128', false);
    $view->assertSee('Members', false);
});

it('renders gold stat card with dark text', function () {
    $view = $this->blade('<x-stat-card :value="15" label="Groups" color="gold" />');

    $view->assertSee('bg-gold-500', false);
    $view->assertSee('text-neutral-900', false);
    $view->assertDontSee('text-white', false);
    $view->assertSee('15', false);
    $view->assertSee('Groups', false);
});

it('uses rounded-xl border radius', function () {
    $view = $this->blade('<x-stat-card :value="1" label="Test" color="coral" />');

    $view->assertSee('rounded-xl', false);
});

it('applies 14px padding', function () {
    $view = $this->blade('<x-stat-card :value="1" label="Test" color="coral" />');

    $view->assertSee('padding: 14px', false);
});

it('renders value with 28px font size and weight 500', function () {
    $view = $this->blade('<x-stat-card :value="99" label="Test" color="coral" />');

    $view->assertSee('font-size: 28px', false);
    $view->assertSee('font-weight: 500', false);
    $view->assertSee('line-height: 1', false);
});

it('renders label with 11px font size and 80% opacity', function () {
    $view = $this->blade('<x-stat-card :value="1" label="Active" color="coral" />');

    $view->assertSee('font-size: 11px', false);
    $view->assertSee('opacity: 0.8', false);
    $view->assertSee('Active', false);
});

it('defaults to coral color when not specified', function () {
    $view = $this->blade('<x-stat-card :value="5" label="Test" />');

    $view->assertSee('bg-coral-500', false);
    $view->assertSee('text-white', false);
});
