<?php

namespace App\Notifications;

use App\Models\GroupJoinRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class JoinRequestDenied extends Notification implements ShouldQueue
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

        $message = (new MailMessage)
            ->subject("Update on your request to join {$group->name}")
            ->line("Your request to join **{$group->name}** was not approved.");

        if ($this->joinRequest->denial_reason) {
            $message->line("Reason: {$this->joinRequest->denial_reason}");
        }

        return $message;
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $data = [
            'group_id' => $this->joinRequest->group_id,
            'join_request_id' => $this->joinRequest->id,
            'message' => "Your request to join {$this->joinRequest->group->name} was not approved.",
            'link' => "/groups/{$this->joinRequest->group->slug}",
        ];

        if ($this->joinRequest->denial_reason) {
            $data['reason'] = $this->joinRequest->denial_reason;
        }

        return $data;
    }
}
