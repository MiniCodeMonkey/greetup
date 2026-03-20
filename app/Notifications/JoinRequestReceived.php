<?php

namespace App\Notifications;

use App\Models\GroupJoinRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class JoinRequestReceived extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(public GroupJoinRequest $joinRequest) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $group = $this->joinRequest->group;
        $user = $this->joinRequest->user;

        return (new MailMessage)
            ->subject("New join request for {$group->name}")
            ->line("**{$user->name}** has requested to join **{$group->name}**.")
            ->action('View Group', url("/groups/{$group->slug}"));
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'group_id' => $this->joinRequest->group_id,
            'user_id' => $this->joinRequest->user_id,
            'join_request_id' => $this->joinRequest->id,
            'message' => "{$this->joinRequest->user->name} requested to join {$this->joinRequest->group->name}.",
            'link' => "/groups/{$this->joinRequest->group->slug}/manage/requests",
        ];
    }
}
