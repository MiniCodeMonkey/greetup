<?php

namespace App\Notifications;

use App\Models\GroupJoinRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class JoinRequestApproved extends Notification implements ShouldQueue
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

        return (new MailMessage)
            ->subject("You've been approved to join {$group->name}!")
            ->line("Your request to join **{$group->name}** has been approved.")
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
            'join_request_id' => $this->joinRequest->id,
            'message' => "Your request to join {$this->joinRequest->group->name} has been approved!",
            'link' => "/groups/{$this->joinRequest->group->slug}",
        ];
    }
}
