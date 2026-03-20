<?php

use App\Livewire\NotificationDropdown;
use App\Models\User;
use App\Notifications\NewEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);
    $this->withoutVite();
});

function createNotificationDropdownUser(): User
{
    return User::factory()->create();
}

function createDatabaseNotification(User $user, array $overrides = []): DatabaseNotification
{
    return DatabaseNotification::create(array_merge([
        'id' => (string) Str::uuid(),
        'type' => NewEvent::class,
        'notifiable_type' => User::class,
        'notifiable_id' => $user->id,
        'data' => [
            'message' => 'New event: Laravel Meetup in PHP Group.',
            'link' => '/groups/php-group/events/laravel-meetup',
        ],
    ], $overrides));
}

it('renders bell icon with zero unread count', function (): void {
    $user = createNotificationDropdownUser();

    Livewire::actingAs($user)
        ->test(NotificationDropdown::class)
        ->assertSee('Notifications')
        ->assertDontSee('data-testid="unread-count"');
});

it('shows unread count badge when notifications exist', function (): void {
    $user = createNotificationDropdownUser();
    createDatabaseNotification($user);
    createDatabaseNotification($user);

    Livewire::actingAs($user)
        ->test(NotificationDropdown::class)
        ->assertSet('unreadCount', 2)
        ->assertSeeHtml('data-testid="unread-count"');
});

it('caps unread count display at 99+', function (): void {
    $user = createNotificationDropdownUser();

    for ($i = 0; $i < 100; $i++) {
        createDatabaseNotification($user);
    }

    Livewire::actingAs($user)
        ->test(NotificationDropdown::class)
        ->assertSet('unreadCount', 100)
        ->assertSee('99+');
});

it('shows recent notifications when dropdown is opened', function (): void {
    $user = createNotificationDropdownUser();
    createDatabaseNotification($user, [
        'data' => [
            'message' => 'Test notification message',
            'link' => '/test',
        ],
    ]);

    Livewire::actingAs($user)
        ->test(NotificationDropdown::class)
        ->call('toggle')
        ->assertSet('isOpen', true)
        ->assertSee('Test notification message');
});

it('shows empty state when no notifications', function (): void {
    $user = createNotificationDropdownUser();

    Livewire::actingAs($user)
        ->test(NotificationDropdown::class)
        ->call('toggle')
        ->assertSee('No notifications yet');
});

it('loads 10 notifications at a time with load more button', function (): void {
    $user = createNotificationDropdownUser();

    for ($i = 0; $i < 15; $i++) {
        createDatabaseNotification($user, [
            'data' => [
                'message' => "Notification {$i}",
                'link' => "/test/{$i}",
            ],
        ]);
    }

    Livewire::actingAs($user)
        ->test(NotificationDropdown::class)
        ->call('toggle')
        ->assertSet('hasMore', true)
        ->assertSeeHtml('data-testid="load-more"');
});

it('loads more notifications when clicking load more', function (): void {
    $user = createNotificationDropdownUser();

    for ($i = 0; $i < 15; $i++) {
        createDatabaseNotification($user, [
            'data' => [
                'message' => "Notification {$i}",
                'link' => "/test/{$i}",
            ],
        ]);
    }

    $component = Livewire::actingAs($user)
        ->test(NotificationDropdown::class);

    $component->call('toggle')
        ->assertSet('hasMore', true);

    $component->call('loadMore')
        ->assertSet('page', 2)
        ->assertSet('hasMore', false);

    expect($component->get('notifications'))->toHaveCount(15);
});

it('marks a notification as read', function (): void {
    $user = createNotificationDropdownUser();
    $notification = createDatabaseNotification($user);

    expect($notification->read_at)->toBeNull();

    Livewire::actingAs($user)
        ->test(NotificationDropdown::class)
        ->assertSet('unreadCount', 1)
        ->call('markAsRead', $notification->id)
        ->assertSet('unreadCount', 0);

    $notification->refresh();
    expect($notification->read_at)->not->toBeNull();
});

it('returns link when marking notification as read', function (): void {
    $user = createNotificationDropdownUser();
    $notification = createDatabaseNotification($user, [
        'data' => [
            'message' => 'Test',
            'link' => '/groups/php-group/events/laravel-meetup',
        ],
    ]);

    $component = Livewire::actingAs($user)
        ->test(NotificationDropdown::class)
        ->call('markAsRead', $notification->id);

    expect($component->get('unreadCount'))->toBe(0);
});

it('marks all notifications as read', function (): void {
    $user = createNotificationDropdownUser();
    createDatabaseNotification($user);
    createDatabaseNotification($user);
    createDatabaseNotification($user);

    Livewire::actingAs($user)
        ->test(NotificationDropdown::class)
        ->assertSet('unreadCount', 3)
        ->call('markAllAsRead')
        ->assertSet('unreadCount', 0);

    expect($user->unreadNotifications()->count())->toBe(0);
});

it('shows mark all as read button when there are unread notifications', function (): void {
    $user = createNotificationDropdownUser();
    createDatabaseNotification($user);

    Livewire::actingAs($user)
        ->test(NotificationDropdown::class)
        ->call('toggle')
        ->assertSee('Mark all as read');
});

it('hides mark all as read button when no unread notifications', function (): void {
    $user = createNotificationDropdownUser();
    createDatabaseNotification($user, ['read_at' => now()]);

    Livewire::actingAs($user)
        ->test(NotificationDropdown::class)
        ->call('toggle')
        ->assertDontSee('Mark all as read');
});

it('highlights unread notifications with green background', function (): void {
    $user = createNotificationDropdownUser();
    createDatabaseNotification($user);

    Livewire::actingAs($user)
        ->test(NotificationDropdown::class)
        ->call('toggle')
        ->assertSeeHtml('bg-green-50');
});

it('displays notification icon based on type', function (): void {
    $user = createNotificationDropdownUser();
    createDatabaseNotification($user);

    Livewire::actingAs($user)
        ->test(NotificationDropdown::class)
        ->call('toggle')
        ->assertSeeHtml('text-violet-500');
});

it('displays notification timestamp', function (): void {
    $user = createNotificationDropdownUser();
    createDatabaseNotification($user);

    Livewire::actingAs($user)
        ->test(NotificationDropdown::class)
        ->call('toggle')
        ->assertSee('second');
});
