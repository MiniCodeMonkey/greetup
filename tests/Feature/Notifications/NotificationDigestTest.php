<?php

use App\Mail\NotificationDigestMail;
use App\Models\Event;
use App\Models\Group;
use App\Models\PendingNotificationDigest;
use App\Models\User;
use App\Notifications\NewEvent;
use App\Services\NotificationService;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);
    $this->service = new NotificationService;
    Mail::fake();
});

it('sends individual emails for fewer than 5 notifications of the same type', function (): void {
    $user = User::factory()->create();
    $group = Group::factory()->create();

    for ($i = 0; $i < 4; $i++) {
        $event = Event::factory()->published()->create(['group_id' => $group->id]);
        $notification = new NewEvent($event, $group);
        $this->service->dispatch($user, $notification, ['group_id' => $group->id]);
    }

    // No notifications batched — all 4 sent individually
    expect(PendingNotificationDigest::count())->toBe(0);

    // 4 web (database) notifications created
    expect($user->notifications()->where('type', NewEvent::class)->count())->toBe(4);
});

it('batches the 5th and subsequent notifications into pending digests', function (): void {
    $user = User::factory()->create();
    $group = Group::factory()->create();

    // Send 4 individually
    for ($i = 0; $i < 4; $i++) {
        $event = Event::factory()->published()->create(['group_id' => $group->id]);
        $notification = new NewEvent($event, $group);
        $this->service->dispatch($user, $notification, ['group_id' => $group->id]);
    }

    expect(PendingNotificationDigest::count())->toBe(0);

    // 5th notification should be batched (email only — web still sends)
    $event5 = Event::factory()->published()->create(['group_id' => $group->id]);
    $this->service->dispatch($user, new NewEvent($event5, $group), ['group_id' => $group->id]);

    expect(PendingNotificationDigest::count())->toBe(1);
    expect($user->notifications()->where('type', NewEvent::class)->count())->toBe(5);

    // 6th notification also batched
    $event6 = Event::factory()->published()->create(['group_id' => $group->id]);
    $this->service->dispatch($user, new NewEvent($event6, $group), ['group_id' => $group->id]);

    expect(PendingNotificationDigest::count())->toBe(2);
    expect($user->notifications()->where('type', NewEvent::class)->count())->toBe(6);
});

it('sends digest email and deletes pending records via scheduled command', function (): void {
    $user = User::factory()->create();
    $group = Group::factory()->create();

    // Create pending digest records directly
    for ($i = 0; $i < 3; $i++) {
        PendingNotificationDigest::create([
            'user_id' => $user->id,
            'notification_type' => NewEvent::class,
            'data' => ['message' => "Event notification {$i}"],
            'created_at' => now(),
        ]);
    }

    expect(PendingNotificationDigest::count())->toBe(3);

    $this->artisan('notifications:send-digests')
        ->expectsOutputToContain('Sent 1 digest email(s)')
        ->assertExitCode(0);

    // Digest email sent
    Mail::assertSent(NotificationDigestMail::class, function (NotificationDigestMail $mail) use ($user): bool {
        return $mail->hasTo($user->email)
            && $mail->user->id === $user->id
            && $mail->notificationType === NewEvent::class
            && $mail->items->count() === 3;
    });

    // Pending records deleted
    expect(PendingNotificationDigest::count())->toBe(0);
});

it('groups pending digests by user and notification type', function (): void {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();

    // User 1 has NewEvent digests
    PendingNotificationDigest::create([
        'user_id' => $user1->id,
        'notification_type' => NewEvent::class,
        'data' => ['message' => 'Event for user 1'],
        'created_at' => now(),
    ]);

    // User 2 has NewEvent digests
    PendingNotificationDigest::create([
        'user_id' => $user2->id,
        'notification_type' => NewEvent::class,
        'data' => ['message' => 'Event for user 2'],
        'created_at' => now(),
    ]);

    $this->artisan('notifications:send-digests')->assertExitCode(0);

    // Two separate digest emails sent
    Mail::assertSent(NotificationDigestMail::class, 2);
    expect(PendingNotificationDigest::count())->toBe(0);
});

it('never batches web (database) notifications', function (): void {
    $user = User::factory()->create();
    $group = Group::factory()->create();

    // Send 6 notifications — web should always fire individually
    for ($i = 0; $i < 6; $i++) {
        $event = Event::factory()->published()->create(['group_id' => $group->id]);
        $notification = new NewEvent($event, $group);
        $this->service->dispatch($user, $notification, ['group_id' => $group->id]);
    }

    // All 6 web notifications sent individually (never batched)
    expect($user->notifications()->where('type', NewEvent::class)->count())->toBe(6);

    // 5th and 6th email batched into pending digests
    expect(PendingNotificationDigest::count())->toBe(2);
});

it('schedules the send-digests command every five minutes', function (): void {
    $schedule = app(Schedule::class);
    $events = collect($schedule->events())->filter(function ($event) {
        return str_contains($event->command, 'notifications:send-digests');
    });

    expect($events)->toHaveCount(1);
    expect($events->first()->expression)->toBe('*/5 * * * *');
});
