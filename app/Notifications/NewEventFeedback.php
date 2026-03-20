<?php

namespace App\Notifications;

use App\Models\Event;
use App\Models\Feedback;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class NewEventFeedback extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(public Feedback $feedback, public Event $event) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'feedback_id' => $this->feedback->id,
            'event_id' => $this->event->id,
            'user_id' => $this->feedback->user_id,
            'rating' => $this->feedback->rating,
            'message' => "{$this->feedback->user->name} left feedback on {$this->event->name}.",
        ];
    }
}
