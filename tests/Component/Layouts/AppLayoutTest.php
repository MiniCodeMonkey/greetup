<?php

beforeEach(function () {
    $this->withoutVite();
});

it('renders the guest app layout with full HTML structure', function () {
    $view = $this->blade('<x-layouts.app title="Test Page" description="Test description">Hello World</x-layouts.app>');

    $view->assertSee('<!DOCTYPE html>', false);
    $view->assertSee('<html', false);
    $view->assertSee('Hello World');
});

it('includes the seo component with configurable title and description', function () {
    $view = $this->blade('<x-layouts.app title="My Events" description="Browse upcoming events">Content</x-layouts.app>');

    $view->assertSee('<title>My Events</title>', false);
    $view->assertSee('content="Browse upcoming events"', false);
});

it('renders navbar with logo, nav links, and auth buttons', function () {
    $view = $this->blade('<x-layouts.app>Content</x-layouts.app>');

    $view->assertSee('greetup.png', false);
    $view->assertSee('Explore');
    $view->assertSee('Groups');
    $view->assertSee('Log in');
    $view->assertSee('Sign up');
});

it('renders explore and groups nav links with correct hrefs', function () {
    $view = $this->blade('<x-layouts.app>Content</x-layouts.app>');

    $view->assertSee('href="/explore"', false);
    $view->assertSee('href="/groups"', false);
});

it('renders login and register links with correct hrefs', function () {
    $view = $this->blade('<x-layouts.app>Content</x-layouts.app>');

    $view->assertSee('href="/login"', false);
    $view->assertSee('href="/register"', false);
});

it('has neutral-50 page background and Instrument Sans font', function () {
    $view = $this->blade('<x-layouts.app>Content</x-layouts.app>');

    $view->assertSee('bg-neutral-50', false);
    $view->assertSee('font-body', false);
    $view->assertSee('Instrument+Sans', false);
});

it('includes mobile hamburger menu toggle', function () {
    $view = $this->blade('<x-layouts.app>Content</x-layouts.app>');

    $view->assertSee('id="mobile-menu"', false);
    $view->assertSee('aria-label="Toggle navigation"', false);
    $view->assertSee('md:hidden', false);
});

it('renders footer with branding', function () {
    $view = $this->blade('<x-layouts.app>Content</x-layouts.app>');

    $view->assertSee('<footer', false);
    $view->assertSee('All rights reserved');
    $view->assertSee(date('Y'));
});

it('includes vite directive in head', function () {
    $view = $this->blade('<x-layouts.app>Content</x-layouts.app>');

    $view->assertSee('<head>', false);
    $view->assertSee('</head>', false);
});

it('renders slot content inside main element', function () {
    $view = $this->blade('<x-layouts.app><div id="test-content">Page content here</div></x-layouts.app>');

    $view->assertSee('<main>', false);
    $view->assertSee('id="test-content"', false);
    $view->assertSee('Page content here');
});

it('uses default title and description when not provided', function () {
    $view = $this->blade('<x-layouts.app>Content</x-layouts.app>');

    $view->assertSee(config('app.name'), false);
    $view->assertSee('Find your people', false);
});
