<?php

namespace App\Console\Commands;

use App\Mail\NotificationDigestMail;
use App\Models\PendingNotificationDigest;
use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

#[Signature('notifications:send-digests')]
#[Description('Send batched notification digest emails and clear pending records')]
class SendNotificationDigests extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $groups = PendingNotificationDigest::query()
            ->select('user_id', 'notification_type')
            ->groupBy('user_id', 'notification_type')
            ->get();

        $sent = 0;

        foreach ($groups as $group) {
            $pendingItems = PendingNotificationDigest::query()
                ->where('user_id', $group->user_id)
                ->where('notification_type', $group->notification_type)
                ->orderBy('created_at')
                ->get();

            if ($pendingItems->isEmpty()) {
                continue;
            }

            $user = User::find($group->user_id);

            if (! $user) {
                PendingNotificationDigest::query()
                    ->where('user_id', $group->user_id)
                    ->where('notification_type', $group->notification_type)
                    ->delete();

                continue;
            }

            Mail::to($user)->send(new NotificationDigestMail(
                user: $user,
                notificationType: $group->notification_type,
                items: $pendingItems,
            ));

            PendingNotificationDigest::query()
                ->where('user_id', $group->user_id)
                ->where('notification_type', $group->notification_type)
                ->whereIn('id', $pendingItems->pluck('id'))
                ->delete();

            $sent++;
        }

        $this->info("Sent {$sent} digest email(s).");

        return self::SUCCESS;
    }
}
