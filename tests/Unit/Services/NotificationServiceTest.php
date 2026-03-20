<?php

use App\Enums\NotificationChannel;
use App\Enums\RsvpStatus;
use App\Models\Block;
use App\Models\Event;
use App\Models\Group;
use App\Models\GroupNotificationMute;
use App\Models\NotificationPreference;
use App\Models\PendingNotificationDigest;
use App\Models\Rsvp;
use App\Models\User;
use App\Notifications\PromotedFromWaitlist;
use App\Services\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Notification as NotificationFacade;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);
    $this->service = new NotificationService;
});

/**
 * A simple test notification for unit testing.
 */
class TestGroupNotification extends Notification
{
    public function __construct(public int $groupId) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)->subject('Test');
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return ['group_id' => $this->groupId];
    }
}

// --- Suspended user ---

it('does not send notifications to suspended users', function (): void {
    NotificationFacade::fake();

    $user = User::factory()->create(['is_suspended' => true]);
    $notification = new TestGroupNotification(1);

    $result = $this->service->dispatch($user, $notification);

    expect($result)->toBeFalse();
    NotificationFacade::assertNothingSent();
});

// --- Blocked user filtering ---

it('does not send notifications when the sender is blocked by the recipient', function (): void {
    NotificationFacade::fake();

    $recipient = User::factory()->create();
    $sender = User::factory()->create();

    Block::create([
        'blocker_id' => $recipient->id,
        'blocked_id' => $sender->id,
        'created_at' => now(),
    ]);

    $notification = new TestGroupNotification(1);

    $result = $this->service->dispatch($recipient, $notification, ['sender_id' => $sender->id]);

    expect($result)->toBeFalse();
    NotificationFacade::assertNothingSent();
});

it('sends notifications when the sender is not blocked', function (): void {
    NotificationFacade::fake();

    $recipient = User::factory()->create();
    $sender = User::factory()->create();

    $notification = new TestGroupNotification(1);

    $result = $this->service->dispatch($recipient, $notification, ['sender_id' => $sender->id]);

    expect($result)->toBeTrue();
    NotificationFacade::assertSentTo($recipient, TestGroupNotification::class);
});

// --- Group mute suppression ---

it('suppresses non-critical notifications for muted groups', function (): void {
    NotificationFacade::fake();

    $user = User::factory()->create();
    $group = Group::factory()->create();

    GroupNotificationMute::create([
        'user_id' => $user->id,
        'group_id' => $group->id,
        'created_at' => now(),
    ]);

    $notification = new TestGroupNotification($group->id);

    $result = $this->service->dispatch($user, $notification, ['group_id' => $group->id]);

    expect($result)->toBeFalse();
    NotificationFacade::assertNothingSent();
});

// --- Critical notification exemption from muting ---

it('sends critical notifications even when the group is muted', function (): void {
    NotificationFacade::fake();

    $user = User::factory()->create();
    $group = Group::factory()->create();
    $event = Event::factory()->published()->create(['group_id' => $group->id]);
    $rsvp = Rsvp::create([
        'event_id' => $event->id,
        'user_id' => $user->id,
        'status' => RsvpStatus::Going,
        'guest_count' => 0,
    ]);

    GroupNotificationMute::create([
        'user_id' => $user->id,
        'group_id' => $group->id,
        'created_at' => now(),
    ]);

    $notification = new PromotedFromWaitlist($event, $rsvp);

    $result = $this->service->dispatch($user, $notification, ['group_id' => $group->id]);

    expect($result)->toBeTrue();
    NotificationFacade::assertSentTo($user, PromotedFromWaitlist::class);
});

// --- Per-type notification preferences ---

it('skips disabled channels based on user preferences', function (): void {
    NotificationFacade::fake();

    $user = User::factory()->create();

    NotificationPreference::create([
        'user_id' => $user->id,
        'channel' => NotificationChannel::Email,
        'type' => TestGroupNotification::class,
        'enabled' => false,
    ]);

    $notification = new TestGroupNotification(1);

    $result = $this->service->dispatch($user, $notification);

    expect($result)->toBeTrue();

    // Should only be sent via database channel, not mail
    NotificationFacade::assertSentTo($user, TestGroupNotification::class, function ($sentNotification, $channels) {
        return $channels === ['database'];
    });
});

it('returns false when all channels are disabled', function (): void {
    NotificationFacade::fake();

    $user = User::factory()->create();

    NotificationPreference::create([
        'user_id' => $user->id,
        'channel' => NotificationChannel::Email,
        'type' => TestGroupNotification::class,
        'enabled' => false,
    ]);

    NotificationPreference::create([
        'user_id' => $user->id,
        'channel' => NotificationChannel::Web,
        'type' => TestGroupNotification::class,
        'enabled' => false,
    ]);

    $notification = new TestGroupNotification(1);

    $result = $this->service->dispatch($user, $notification);

    expect($result)->toBeFalse();
    NotificationFacade::assertNothingSent();
});

// --- Digest batching ---

it('sends first four notifications individually via email', function (): void {
    NotificationFacade::fake();

    $user = User::factory()->create();

    for ($i = 0; $i < 4; $i++) {
        $notification = new TestGroupNotification(1);
        $result = $this->service->dispatch($user, $notification);
        expect($result)->toBeTrue();
    }

    // All 4 should be sent directly (no digests stored)
    expect(PendingNotificationDigest::count())->toBe(0);

    // Each dispatch sends via mail + database, so 4 dispatches = 8 channel sends
    NotificationFacade::assertSentToTimes($user, TestGroupNotification::class, 8);
});

it('batches the fifth notification into digest instead of sending email', function (): void {
    NotificationFacade::fake();

    $user = User::factory()->create();

    // Create 4 existing digest records within the window to simulate prior sends
    for ($i = 0; $i < 4; $i++) {
        PendingNotificationDigest::create([
            'user_id' => $user->id,
            'notification_type' => TestGroupNotification::class,
            'data' => ['group_id' => 1],
            'created_at' => now()->subMinutes(5),
        ]);
    }

    $notification = new TestGroupNotification(1);
    $result = $this->service->dispatch($user, $notification);

    expect($result)->toBeTrue();

    // The 5th should be stored as a digest, not sent via email
    expect(PendingNotificationDigest::count())->toBe(5);

    // Web/database notification should still be sent directly
    NotificationFacade::assertSentTo($user, TestGroupNotification::class, function ($sentNotification, $channels) {
        return $channels === ['database'];
    });
});

it('does not batch web notifications even when digest threshold is exceeded', function (): void {
    NotificationFacade::fake();

    $user = User::factory()->create();

    // Create enough prior digests to exceed threshold
    for ($i = 0; $i < 5; $i++) {
        PendingNotificationDigest::create([
            'user_id' => $user->id,
            'notification_type' => TestGroupNotification::class,
            'data' => ['group_id' => 1],
            'created_at' => now()->subMinutes(3),
        ]);
    }

    $notification = new TestGroupNotification(1);
    $result = $this->service->dispatch($user, $notification);

    expect($result)->toBeTrue();

    // Database/web notification should always be sent directly
    NotificationFacade::assertSentTo($user, TestGroupNotification::class, function ($sentNotification, $channels) {
        return $channels === ['database'];
    });
});
