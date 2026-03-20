<?php

namespace App\Mail;

use App\Models\PendingNotificationDigest;
use App\Models\User;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Support\Collection;

class NotificationDigestMail extends Mailable
{
    /**
     * Create a new message instance.
     *
     * @param  Collection<int, PendingNotificationDigest>  $items
     */
    public function __construct(
        public User $user,
        public string $notificationType,
        public Collection $items,
    ) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $shortType = class_basename($this->notificationType);
        $count = $this->items->count();

        return new Envelope(
            subject: "Notification Digest: {$count} {$shortType} notifications",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.notification-digest',
        );
    }
}
