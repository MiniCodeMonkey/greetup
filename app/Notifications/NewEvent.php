<?php

namespace App\Notifications;

use App\Models\Event;
use App\Models\Group;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewEvent extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(public Event $event, public Group $group) {}

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
        return (new MailMessage)
            ->subject("New event: {$this->event->name}")
            ->line("A new event **{$this->event->name}** has been created in **{$this->group->name}**.")
            ->line("It starts on {$this->event->starts_at->format('M j, Y g:i A')}.");
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'event_id' => $this->event->id,
            'group_id' => $this->group->id,
            'message' => "New event: {$this->event->name} in {$this->group->name}.",
        ];
    }
}
