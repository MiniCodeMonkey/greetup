<?php

namespace App\Notifications;

use App\Models\DirectMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewDirectMessage extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public DirectMessage $message) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("New message from {$this->message->user->name}")
            ->line("{$this->message->user->name} sent you a message.")
            ->line("\"{$this->message->body}\"");
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'message_id' => $this->message->id,
            'conversation_id' => $this->message->conversation_id,
            'user_id' => $this->message->user_id,
            'message' => "{$this->message->user->name} sent you a message.",
        ];
    }
}
