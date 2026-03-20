<?php

namespace App\Notifications;

use App\Enums\GroupRole;
use App\Models\Group;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RoleChanged extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(public Group $group, public GroupRole $oldRole, public GroupRole $newRole) {}

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
        $oldLabel = ucfirst(str_replace('_', ' ', $this->oldRole->value));
        $newLabel = ucfirst(str_replace('_', ' ', $this->newRole->value));

        return (new MailMessage)
            ->subject("Your role in {$this->group->name} has changed")
            ->line("Your role in **{$this->group->name}** has been changed from **{$oldLabel}** to **{$newLabel}**.")
            ->action('View Group', url("/groups/{$this->group->slug}"));
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $oldLabel = ucfirst(str_replace('_', ' ', $this->oldRole->value));
        $newLabel = ucfirst(str_replace('_', ' ', $this->newRole->value));

        return [
            'group_id' => $this->group->id,
            'message' => "Your role in {$this->group->name} has been changed from {$oldLabel} to {$newLabel}.",
            'link' => "/groups/{$this->group->slug}",
        ];
    }
}
