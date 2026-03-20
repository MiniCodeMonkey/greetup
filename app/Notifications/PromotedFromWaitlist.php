<?php

namespace App\Notifications;

use App\Models\Event;
use App\Models\Rsvp;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PromotedFromWaitlist extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(public Event $event, public Rsvp $rsvp) {}

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
            ->subject("You're in! Spot opened for {$this->event->title}")
            ->line("Great news! A spot has opened up and you've been promoted from the waitlist for **{$this->event->title}**.")
            ->action('View Event', url("/events/{$this->event->id}"))
            ->line('Your RSVP status has been updated to going.');
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
            'rsvp_id' => $this->rsvp->id,
            'message' => "You've been promoted from the waitlist for {$this->event->title}.",
        ];
    }
}
