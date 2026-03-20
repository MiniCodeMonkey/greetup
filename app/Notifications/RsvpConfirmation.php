<?php

namespace App\Notifications;

use App\Models\Event;
use App\Models\Rsvp;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RsvpConfirmation extends Notification implements ShouldQueue
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
        $status = $this->rsvp->status->value === 'waitlisted'
            ? "You're on the waitlist for"
            : "You're going to";

        return (new MailMessage)
            ->subject("{$status} {$this->event->name}")
            ->line("{$status} **{$this->event->name}**.")
            ->action('View Event', url("/groups/{$this->event->group->slug}/events/{$this->event->slug}"))
            ->line('We look forward to seeing you there!');
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
            'status' => $this->rsvp->status->value,
            'message' => $this->rsvp->status->value === 'waitlisted'
                ? "You're on the waitlist for {$this->event->name}."
                : "You're going to {$this->event->name}.",
            'link' => "/groups/{$this->event->group->slug}/events/{$this->event->slug}",
        ];
    }
}
