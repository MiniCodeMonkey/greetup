<?php

namespace App\Notifications;

use App\Models\Discussion;
use App\Models\Group;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class NewDiscussion extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(public Discussion $discussion, public Group $group) {}

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
            'discussion_id' => $this->discussion->id,
            'group_id' => $this->group->id,
            'user_id' => $this->discussion->user_id,
            'message' => "{$this->discussion->user->name} started a discussion: {$this->discussion->title}.",
            'link' => "/groups/{$this->group->slug}/discussions/{$this->discussion->slug}",
        ];
    }
}
