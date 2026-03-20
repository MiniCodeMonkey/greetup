<?php

namespace App\Notifications;

use App\Models\Group;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MemberRemoved extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(public Group $group, public ?string $reason = null) {}

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
        $message = (new MailMessage)
            ->subject("You have been removed from {$this->group->name}")
            ->line("You have been removed from **{$this->group->name}**.");

        if ($this->reason) {
            $message->line("Reason: {$this->reason}");
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
            'group_id' => $this->group->id,
            'message' => "You have been removed from {$this->group->name}.",
            'link' => "/groups/{$this->group->slug}",
        ];

        if ($this->reason) {
            $data['reason'] = $this->reason;
        }

        return $data;
    }
}
