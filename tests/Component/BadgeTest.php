<?php

it('renders in_person badge with correct colors', function () {
    $view = $this->blade('<x-badge type="in_person" />');

    $view->assertSee('bg-coral-50', false);
    $view->assertSee('text-coral-900', false);
    $view->assertSee('In person', false);
});

it('renders online badge with correct colors', function () {
    $view = $this->blade('<x-badge type="online" />');

    $view->assertSee('bg-violet-50', false);
    $view->assertSee('text-violet-900', false);
    $view->assertSee('Online', false);
});

it('renders hybrid badge with correct colors', function () {
    $view = $this->blade('<x-badge type="hybrid" />');

    $view->assertSee('bg-green-50', false);
    $view->assertSee('text-green-700', false);
    $view->assertSee('Hybrid', false);
});

it('renders going badge with correct colors', function () {
    $view = $this->blade('<x-badge type="going" />');

    $view->assertSee('bg-green-50', false);
    $view->assertSee('text-green-700', false);
    $view->assertSee('Going', false);
});

it('renders waitlisted badge with correct colors', function () {
    $view = $this->blade('<x-badge type="waitlisted" />');

    $view->assertSee('bg-gold-50', false);
    $view->assertSee('text-gold-900', false);
    $view->assertSee('Waitlisted', false);
});

it('renders cancelled badge with correct colors', function () {
    $view = $this->blade('<x-badge type="cancelled" />');

    $view->assertSee('bg-red-50', false);
    $view->assertSee('text-red-900', false);
    $view->assertSee('Cancelled', false);
});

it('renders almost_full badge with correct colors', function () {
    $view = $this->blade('<x-badge type="almost_full" />');

    $view->assertSee('bg-gold-50', false);
    $view->assertSee('text-gold-900', false);
    $view->assertSee('Almost full', false);
});

it('applies rounded-sm border radius', function () {
    $view = $this->blade('<x-badge type="going" />');

    $view->assertSee('rounded-sm', false);
});

it('accepts custom label override', function () {
    $view = $this->blade('<x-badge type="going" label="Confirmed" />');

    $view->assertSee('Confirmed', false);
    $view->assertDontSee('Going', false);
});
