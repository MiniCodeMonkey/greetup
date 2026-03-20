<?php

namespace App\Policies;

use App\Enums\GroupRole;
use App\Enums\RsvpStatus;
use App\Models\Event;
use App\Models\EventChatMessage;
use App\Models\Group;
use App\Models\User;

class EventChatPolicy
{
    /**
     * Get the user's membership record for the group.
     *
     * @return array{role: GroupRole, is_banned: bool}|null
     */
    private function getMembership(User $user, Group $group): ?array
    {
        $pivot = $group->members()->where('user_id', $user->id)->first()?->pivot;

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
     * Check if user is a non-suspended, non-banned member with at least the given role.
     */
    private function hasGroupRole(User $user, Group $group, GroupRole $minimumRole): bool
    {
        if ($user->is_suspended) {
            return false;
        }

        $membership = $this->getMembership($user, $group);

        if (! $membership || $membership['is_banned']) {
            return false;
        }

        return $membership['role']->isAtLeast($minimumRole);
    }

    /**
     * Check if user is a non-suspended, non-banned member of the group.
     */
    private function isActiveMember(User $user, Group $group): bool
    {
        if ($user->is_suspended) {
            return false;
        }

        $membership = $this->getMembership($user, $group);

        return $membership !== null && ! $membership['is_banned'];
    }

    /**
     * Check if the user has RSVP'd "Going" to the event.
     */
    private function isRsvpGoing(User $user, Event $event): bool
    {
        return $event->rsvps()
            ->where('user_id', $user->id)
            ->where('status', RsvpStatus::Going)
            ->exists();
    }

    /**
     * Send a message: requires RSVP Going or group membership.
     * Returns false if chat is disabled.
     */
    public function send(User $user, Event $event): bool
    {
        if (! $event->is_chat_enabled) {
            return false;
        }

        if ($user->is_suspended) {
            return false;
        }

        if ($this->isRsvpGoing($user, $event)) {
            return true;
        }

        return $this->isActiveMember($user, $event->group);
    }

    /**
     * Edit own message only.
     */
    public function edit(User $user, EventChatMessage $message): bool
    {
        if (! $message->event->is_chat_enabled) {
            return false;
        }

        if ($user->is_suspended) {
            return false;
        }

        return $message->user_id === $user->id;
    }

    /**
     * Delete own message, or leadership (event_organizer+) can delete any message.
     */
    public function delete(User $user, EventChatMessage $message): bool
    {
        if (! $message->event->is_chat_enabled) {
            return false;
        }

        if ($user->is_suspended) {
            return false;
        }

        if ($message->user_id === $user->id) {
            return true;
        }

        return $this->hasGroupRole($user, $message->event->group, GroupRole::EventOrganizer);
    }
}
