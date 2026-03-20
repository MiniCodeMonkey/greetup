<?php

use App\Events\NotificationSent;
use Illuminate\Broadcasting\PrivateChannel;

it('broadcasts on the correct private channel', function (): void {
    $event = new NotificationSent(userId: 42, unreadCount: 5);

    $channels = $event->broadcastOn();

    expect($channels)->toHaveCount(1);
    expect($channels[0])->toBeInstanceOf(PrivateChannel::class);
    expect($channels[0]->name)->toBe('private-user.42.notifications');
});

it('contains the unread count', function (): void {
    $event = new NotificationSent(userId: 1, unreadCount: 10);

    expect($event->unreadCount)->toBe(10);
    expect($event->userId)->toBe(1);
});
