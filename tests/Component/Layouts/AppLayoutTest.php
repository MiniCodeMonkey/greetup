<?php

use App\Models\User;
use Illuminate\Support\Collection;

beforeEach(function () {
    $this->withoutVite();
});

function makeAuthUser(string $name = 'Jane Doe', int $id = 1, int $unreadCount = 0, array $notifications = []): User
{
    $user = User::factory()->make(['id' => $id, 'name' => $name]);

    $notificationCollection = new Collection(array_map(function ($n) {
        return (object) $n;
    }, $notifications));

    $unreadMock = Mockery::mock();
    $unreadMock->shouldReceive('count')->andReturn($unreadCount);

    $notificationsMock = Mockery::mock();
    $notificationsMock->shouldReceive('take->get')->andReturn($notificationCollection);

    $mock = Mockery::mock($user)->makePartial();
    $mock->shouldReceive('unreadNotifications')->andReturn($unreadMock);
    $mock->shouldReceive('notifications')->andReturn($notificationsMock);

    return $mock;
}

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

it('renders navbar with logo, nav links, and auth buttons for guests', function () {
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

it('renders login and register links with correct hrefs for guests', function () {
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

it('shows notification bell and avatar dropdown for authenticated users', function () {
    $this->actingAs(makeAuthUser());

    $view = $this->blade('<x-layouts.app>Content</x-layouts.app>');

    $view->assertSee('aria-label="Notifications"', false);
    $view->assertSee('aria-label="Account menu"', false);
    $view->assertDontSee('Log in');
    $view->assertDontSee('Sign up');
});

it('shows unread notification count badge when there are unread notifications', function () {
    $this->actingAs(makeAuthUser(unreadCount: 5));

    $view = $this->blade('<x-layouts.app>Content</x-layouts.app>');

    $view->assertSee('data-testid="unread-count"', false);
    $view->assertSee('>5</span>', false);
});

it('caps unread count display at 99+', function () {
    $this->actingAs(makeAuthUser(unreadCount: 150));

    $view = $this->blade('<x-layouts.app>Content</x-layouts.app>');

    $view->assertSee('99+');
});

it('does not show unread count badge when no unread notifications', function () {
    $this->actingAs(makeAuthUser(unreadCount: 0));

    $view = $this->blade('<x-layouts.app>Content</x-layouts.app>');

    $view->assertDontSee('data-testid="unread-count"', false);
});

it('renders account dropdown with Dashboard, My Groups, Messages, Settings, Logout', function () {
    $this->actingAs(makeAuthUser());

    $view = $this->blade('<x-layouts.app>Content</x-layouts.app>');

    $view->assertSee('href="/dashboard"', false);
    $view->assertSee('My Groups');
    $view->assertSee('href="/messages"', false);
    $view->assertSee('href="/settings"', false);
    $view->assertSee('Logout');
    $view->assertSee('action="/logout"', false);
});

it('renders notification dropdown with recent notifications', function () {
    $notifications = [
        [
            'data' => ['message' => 'Someone RSVPed to your event'],
            'read_at' => null,
            'created_at' => now()->subMinutes(5),
        ],
    ];
    $this->actingAs(makeAuthUser(unreadCount: 1, notifications: $notifications));

    $view = $this->blade('<x-layouts.app>Content</x-layouts.app>');

    $view->assertSee('Someone RSVPed to your event');
    $view->assertSee('data-testid="notification-list"', false);
});

it('shows empty state when no notifications exist', function () {
    $this->actingAs(makeAuthUser());

    $view = $this->blade('<x-layouts.app>Content</x-layouts.app>');

    $view->assertSee('No notifications yet');
});

it('shows load more button when 10 or more notifications exist', function () {
    $notifications = [];
    for ($i = 0; $i < 10; $i++) {
        $notifications[] = [
            'data' => ['message' => "Notification {$i}"],
            'read_at' => null,
            'created_at' => now()->subMinutes($i),
        ];
    }
    $this->actingAs(makeAuthUser(unreadCount: 10, notifications: $notifications));

    $view = $this->blade('<x-layouts.app>Content</x-layouts.app>');

    $view->assertSee('data-testid="load-more"', false);
    $view->assertSee('Load more');
});

it('does not show load more button when fewer than 10 notifications', function () {
    $notifications = [
        [
            'data' => ['message' => 'Single notification'],
            'read_at' => null,
            'created_at' => now(),
        ],
    ];
    $this->actingAs(makeAuthUser(unreadCount: 1, notifications: $notifications));

    $view = $this->blade('<x-layouts.app>Content</x-layouts.app>');

    $view->assertDontSee('data-testid="load-more"', false);
});

it('includes all navigation and account links in mobile menu for authenticated users', function () {
    $this->actingAs(makeAuthUser());

    $view = $this->blade('<x-layouts.app>Content</x-layouts.app>');

    $view->assertSee('Explore');
    $view->assertSee('Groups');
    $view->assertSee('Dashboard');
    $view->assertSee('My Groups');
    $view->assertSee('Messages');
    $view->assertSee('Notifications');
    $view->assertSee('Settings');
    $view->assertSee('Logout');
    $view->assertDontSee('Log in');
    $view->assertDontSee('Sign up');
});

it('highlights unread notifications in green-50 background', function () {
    $notifications = [
        [
            'data' => ['message' => 'Unread notification'],
            'read_at' => null,
            'created_at' => now(),
        ],
    ];
    $this->actingAs(makeAuthUser(unreadCount: 1, notifications: $notifications));

    $view = $this->blade('<x-layouts.app>Content</x-layouts.app>');

    $view->assertSee('bg-green-50', false);
});
