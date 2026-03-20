<?php

namespace App\Policies;

use App\Enums\GroupRole;
use App\Models\Event;
use App\Models\Group;
use App\Models\User;

class EventPolicy
{
    /**
     * Get the user's membership record for the event's group.
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
     * Check if user is a host of the given event.
     */
    private function isHost(User $user, Event $event): bool
    {
        return $event->hosts()->where('user_id', $user->id)->exists();
    }

    /**
     * Check if user is a non-suspended, non-banned member of the event's group.
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
     * Any user can view an event.
     */
    public function view(User $user, Event $event): bool
    {
        return true;
    }

    /**
     * Event organizer+ within the group can create events.
     */
    public function create(User $user, Group $group): bool
    {
        return $this->hasGroupRole($user, $group, GroupRole::EventOrganizer);
    }

    /**
     * Event hosts can edit their own event; event organizer+ can edit any group event.
     */
    public function update(User $user, Event $event): bool
    {
        if ($user->is_suspended) {
            return false;
        }

        if ($this->hasGroupRole($user, $event->group, GroupRole::EventOrganizer)) {
            return true;
        }

        if ($this->isActiveMember($user, $event->group) && $this->isHost($user, $event)) {
            return true;
        }

        return false;
    }

    /**
     * Event organizer+ within the group can cancel events.
     */
    public function cancel(User $user, Event $event): bool
    {
        return $this->hasGroupRole($user, $event->group, GroupRole::EventOrganizer);
    }

    /**
     * Event hosts can manage attendees for their event; event organizer+ can manage any.
     */
    public function manageAttendees(User $user, Event $event): bool
    {
        if ($user->is_suspended) {
            return false;
        }

        if ($this->hasGroupRole($user, $event->group, GroupRole::EventOrganizer)) {
            return true;
        }

        if ($this->isActiveMember($user, $event->group) && $this->isHost($user, $event)) {
            return true;
        }

        return false;
    }

    /**
     * Event hosts can check in attendees for their event; event organizer+ can check in any.
     */
    public function checkIn(User $user, Event $event): bool
    {
        if ($user->is_suspended) {
            return false;
        }

        if ($this->hasGroupRole($user, $event->group, GroupRole::EventOrganizer)) {
            return true;
        }

        if ($this->isActiveMember($user, $event->group) && $this->isHost($user, $event)) {
            return true;
        }

        return false;
    }

    /**
     * Verified, non-suspended members can RSVP. Non-members and unverified users cannot.
     */
    public function rsvp(User $user, Event $event): bool
    {
        if ($user->is_suspended) {
            return false;
        }

        if (! $user->hasVerifiedEmail()) {
            return false;
        }

        return $this->isActiveMember($user, $event->group);
    }
}
