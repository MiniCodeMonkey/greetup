<?php

namespace App\Notifications;

use App\Models\Discussion;
use App\Models\DiscussionReply;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewDiscussionReply extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(public DiscussionReply $reply, public Discussion $discussion) {}

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
            ->subject("New reply in discussion: {$this->discussion->title}")
            ->line("{$this->reply->user->name} replied to the discussion **{$this->discussion->title}**.")
            ->line("\"{$this->reply->body}\"")
            ->action('View Discussion', url("/groups/{$this->discussion->group->slug}/discussions/{$this->discussion->slug}"));
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'reply_id' => $this->reply->id,
            'discussion_id' => $this->discussion->id,
            'group_id' => $this->discussion->group_id,
            'user_id' => $this->reply->user_id,
            'message' => "{$this->reply->user->name} replied to {$this->discussion->title}.",
            'link' => "/groups/{$this->discussion->group->slug}/discussions/{$this->discussion->slug}",
        ];
    }
}
