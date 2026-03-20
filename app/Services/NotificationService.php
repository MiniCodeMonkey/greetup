<?php

namespace App\Services;

use App\Enums\NotificationChannel;
use App\Models\Block;
use App\Models\GroupNotificationMute;
use App\Models\NotificationPreference;
use App\Models\PendingNotificationDigest;
use App\Models\User;
use App\Notifications\PromotedFromWaitlist;
use Illuminate\Notifications\Notification;

class NotificationService
{
    /**
     * Notification types that are never suppressed by group mutes.
     *
     * @var list<class-string<Notification>>
     */
    public const CRITICAL_NOTIFICATIONS = [
        PromotedFromWaitlist::class,
        'App\Notifications\JoinRequestApproved',
        'App\Notifications\MemberRemoved',
        'App\Notifications\MemberBanned',
        'App\Notifications\AccountSuspended',
    ];

    /**
     * Number of same-type notifications within the digest window that triggers batching.
     */
    public const DIGEST_THRESHOLD = 5;

    /**
     * Digest window in minutes.
     */
    public const DIGEST_WINDOW_MINUTES = 15;

    /**
     * Dispatch a notification to a recipient, respecting mutes, blocks, preferences, and digest batching.
     *
     * @param  array{group_id?: int, sender_id?: int}  $context
     */
    public function dispatch(User $recipient, Notification $notification, array $context = []): bool
    {
        if ($recipient->is_suspended) {
            return false;
        }

        $senderId = $context['sender_id'] ?? null;

        if ($senderId !== null && $this->isBlocked($recipient, $senderId)) {
            return false;
        }

        $groupId = $context['group_id'] ?? null;
        $isCritical = $this->isCritical($notification);

        if (! $isCritical && $groupId !== null && $this->isGroupMuted($recipient, $groupId)) {
            return false;
        }

        $channels = $this->resolveChannels($recipient, $notification);

        if (empty($channels)) {
            return false;
        }

        $notificationType = get_class($notification);
        $emailChannel = in_array('mail', $channels);
        $nonEmailChannels = array_values(array_filter($channels, fn (string $ch): bool => $ch !== 'mail'));

        // Always send non-email channels immediately (web/database notifications are never batched)
        if (! empty($nonEmailChannels)) {
            $recipient->notifyNow(clone $notification, $nonEmailChannels);
        }

        // Handle email channel with digest batching
        if ($emailChannel) {
            if ($this->shouldBatchDigest($recipient, $notificationType)) {
                $this->storeDigest($recipient, $notification);
            } else {
                $recipient->notifyNow(clone $notification, ['mail']);
            }
        }

        return true;
    }

    /**
     * Check if the notification type is critical (exempt from group muting).
     */
    public function isCritical(Notification $notification): bool
    {
        return in_array(get_class($notification), self::CRITICAL_NOTIFICATIONS, true);
    }

    /**
     * Check if the sender is blocked by the recipient.
     */
    private function isBlocked(User $recipient, int $senderId): bool
    {
        return Block::query()
            ->where('blocker_id', $recipient->id)
            ->where('blocked_id', $senderId)
            ->exists();
    }

    /**
     * Check if the recipient has muted the given group's notifications.
     */
    private function isGroupMuted(User $recipient, int $groupId): bool
    {
        return GroupNotificationMute::query()
            ->where('user_id', $recipient->id)
            ->where('group_id', $groupId)
            ->exists();
    }

    /**
     * Resolve which channels to send on, filtering by user preferences.
     *
     * @return list<string>
     */
    private function resolveChannels(User $recipient, Notification $notification): array
    {
        $defaultChannels = $notification->via($recipient);
        $notificationType = get_class($notification);

        $disabledChannels = NotificationPreference::query()
            ->where('user_id', $recipient->id)
            ->where('type', $notificationType)
            ->where('enabled', false)
            ->pluck('channel')
            ->map(fn ($channel): string => $this->channelToDriver($channel))
            ->all();

        return array_values(array_filter(
            $defaultChannels,
            fn (string $channel): bool => ! in_array($channel, $disabledChannels, true),
        ));
    }

    /**
     * Map a NotificationChannel enum value to its Laravel driver name.
     */
    private function channelToDriver(mixed $channel): string
    {
        if ($channel instanceof NotificationChannel) {
            $channel = $channel->value;
        }

        return match ($channel) {
            'email' => 'mail',
            'web' => 'database',
            'push' => 'push',
            default => (string) $channel,
        };
    }

    /**
     * Determine if the notification should be batched into a digest.
     * Returns true when the recipient already has DIGEST_THRESHOLD - 1 or more
     * notifications of this type within the digest window.
     */
    private function shouldBatchDigest(User $recipient, string $notificationType): bool
    {
        $windowStart = now()->subMinutes(self::DIGEST_WINDOW_MINUTES);

        $recentCount = PendingNotificationDigest::query()
            ->where('user_id', $recipient->id)
            ->where('notification_type', $notificationType)
            ->where('created_at', '>=', $windowStart)
            ->count();

        return $recentCount >= self::DIGEST_THRESHOLD - 1;
    }

    /**
     * Store notification data in the pending digest table.
     */
    private function storeDigest(User $recipient, Notification $notification): void
    {
        PendingNotificationDigest::create([
            'user_id' => $recipient->id,
            'notification_type' => get_class($notification),
            'data' => $notification->toArray($recipient),
            'created_at' => now(),
        ]);
    }
}
