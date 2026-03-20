<?php

namespace App\Policies;

use App\Enums\GroupRole;
use App\Enums\RsvpStatus;
use App\Models\Event;
use App\Models\Feedback;
use App\Models\User;

class FeedbackPolicy
{
    /**
     * Get the user's membership record for the event's group.
     *
     * @return array{role: GroupRole, is_banned: bool}|null
     */
    private function getMembership(User $user, Event $event): ?array
    {
        $pivot = $event->group->members()->where('user_id', $user->id)->first()?->pivot;

        if (! $pivot) {
            return null;
        }

        $role = $pivot->role instanceof GroupRole
            ? $pivot->role
            : GroupRole::from($pivot->role);

        return [
            'role' => $role,
            'is_banned' => (bool) $pivot->is_banned,
        ];
    }

    /**
     * Determine if the event has ended.
     */
    private function eventHasEnded(Event $event): bool
    {
        $endsAt = $event->ends_at ?? $event->starts_at->addHours(3);

        return now()->greaterThanOrEqualTo($endsAt);
    }

    /**
     * Attendees who RSVP'd Going can create feedback after event ends.
     */
    public function create(User $user, Event $event): bool
    {
        if ($user->is_suspended) {
            return false;
        }

        if (! $this->eventHasEnded($event)) {
            return false;
        }

        $membership = $this->getMembership($user, $event);

        if (! $membership || $membership['is_banned']) {
            return false;
        }

        $hasGoingRsvp = $event->rsvps()
            ->where('user_id', $user->id)
            ->where('status', RsvpStatus::Going)
            ->exists();

        if (! $hasGoingRsvp) {
            return false;
        }

        $alreadySubmitted = $event->feedback()
            ->where('user_id', $user->id)
            ->exists();

        return ! $alreadySubmitted;
    }

    /**
     * Organizer+ sees all feedback with attribution; members see aggregate only.
     */
    public function viewAttribution(User $user, Event $event): bool
    {
        if ($user->is_suspended) {
            return false;
        }

        $membership = $this->getMembership($user, $event);

        if (! $membership || $membership['is_banned']) {
            return false;
        }

        return $membership['role']->isAtLeast(GroupRole::Organizer);
    }
}
