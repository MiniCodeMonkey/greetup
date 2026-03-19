<?php

it('renders all tabs', function () {
    $tabs = [
        ['label' => 'About', 'href' => '/group/1', 'active' => true],
        ['label' => 'Events', 'href' => '/group/1/events', 'active' => false],
        ['label' => 'Members', 'href' => '/group/1/members', 'active' => false],
    ];

    $view = $this->blade('<x-tab-bar :tabs="$tabs" />', ['tabs' => $tabs]);

    $view->assertSee('About', false);
    $view->assertSee('Events', false);
    $view->assertSee('Members', false);
    $view->assertSee('/group/1"', false);
    $view->assertSee('/group/1/events', false);
    $view->assertSee('/group/1/members', false);
});

it('renders active tab with green-500 text and 2px border', function () {
    $tabs = [
        ['label' => 'About', 'href' => '/group/1', 'active' => true],
        ['label' => 'Events', 'href' => '/group/1/events', 'active' => false],
    ];

    $view = $this->blade('<x-tab-bar :tabs="$tabs" />', ['tabs' => $tabs]);

    $view->assertSee('text-green-500', false);
    $view->assertSee('font-medium', false);
    $view->assertSee('border-bottom: 2px solid', false);
});

it('renders inactive tabs with neutral-500 text', function () {
    $tabs = [
        ['label' => 'About', 'href' => '/group/1', 'active' => false],
    ];

    $view = $this->blade('<x-tab-bar :tabs="$tabs" />', ['tabs' => $tabs]);

    $view->assertSee('text-neutral-500', false);
    $view->assertDontSee('text-green-500', false);
    $view->assertDontSee('border-bottom: 2px solid', false);
});

it('has bottom border on the row', function () {
    $tabs = [
        ['label' => 'About', 'href' => '/group/1', 'active' => true],
    ];

    $view = $this->blade('<x-tab-bar :tabs="$tabs" />', ['tabs' => $tabs]);

    $view->assertSee('border-bottom: 0.5px solid', false);
});

it('is horizontally scrollable with hidden scrollbar', function () {
    $tabs = [
        ['label' => 'About', 'href' => '/group/1', 'active' => true],
    ];

    $view = $this->blade('<x-tab-bar :tabs="$tabs" />', ['tabs' => $tabs]);

    $view->assertSee('overflow-x-auto', false);
    $view->assertSee('scrollbar-width:none', false);
});
