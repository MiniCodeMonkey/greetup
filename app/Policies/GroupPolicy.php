<?php

namespace App\Policies;

use App\Enums\GroupRole;
use App\Models\Group;
use App\Models\User;

class GroupPolicy
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
    private function hasRole(User $user, Group $group, GroupRole $minimumRole): bool
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
     * Any verified user can create a group.
     */
    public function create(User $user): bool
    {
        return ! $user->is_suspended && $user->hasVerifiedEmail();
    }

    /**
     * Any user can view a group.
     */
    public function view(User $user, Group $group): bool
    {
        return true;
    }

    /**
     * Verified, non-member, non-banned users can join.
     */
    public function join(User $user, Group $group): bool
    {
        if ($user->is_suspended) {
            return false;
        }

        if (! $user->hasVerifiedEmail()) {
            return false;
        }

        $membership = $this->getMembership($user, $group);

        if ($membership === null) {
            return true;
        }

        if ($membership['is_banned']) {
            return false;
        }

        // Already a member
        return false;
    }

    /**
     * Any member can leave, except the organizer (must transfer first).
     */
    public function leave(User $user, Group $group): bool
    {
        if ($user->is_suspended) {
            return false;
        }

        $membership = $this->getMembership($user, $group);

        if (! $membership || $membership['is_banned']) {
            return false;
        }

        // Organizer cannot leave without transferring ownership
        if ($membership['role'] === GroupRole::Organizer) {
            return false;
        }

        return true;
    }

    /**
     * Event organizer+ can create events.
     */
    public function createEvent(User $user, Group $group): bool
    {
        return $this->hasRole($user, $group, GroupRole::EventOrganizer);
    }

    /**
     * Event organizer+ can edit any event.
     */
    public function editAnyEvent(User $user, Group $group): bool
    {
        return $this->hasRole($user, $group, GroupRole::EventOrganizer);
    }

    /**
     * Event organizer+ can cancel events.
     */
    public function cancelEvent(User $user, Group $group): bool
    {
        return $this->hasRole($user, $group, GroupRole::EventOrganizer);
    }

    /**
     * Event organizer+ can manage RSVPs.
     */
    public function manageRsvps(User $user, Group $group): bool
    {
        return $this->hasRole($user, $group, GroupRole::EventOrganizer);
    }

    /**
     * Event organizer+ can check in attendees.
     */
    public function checkInAttendees(User $user, Group $group): bool
    {
        return $this->hasRole($user, $group, GroupRole::EventOrganizer);
    }

    /**
     * Event organizer+ can send group messages.
     */
    public function sendGroupMessages(User $user, Group $group): bool
    {
        return $this->hasRole($user, $group, GroupRole::EventOrganizer);
    }

    /**
     * Event organizer+ can assign event hosts.
     */
    public function assignEventHosts(User $user, Group $group): bool
    {
        return $this->hasRole($user, $group, GroupRole::EventOrganizer);
    }

    /**
     * Assistant organizer+ can accept/deny join requests.
     */
    public function acceptRequests(User $user, Group $group): bool
    {
        return $this->hasRole($user, $group, GroupRole::AssistantOrganizer);
    }

    /**
     * Assistant organizer+ can remove members.
     */
    public function removeMembers(User $user, Group $group): bool
    {
        return $this->hasRole($user, $group, GroupRole::AssistantOrganizer);
    }

    /**
     * Assistant organizer+ can ban members.
     */
    public function banMembers(User $user, Group $group): bool
    {
        return $this->hasRole($user, $group, GroupRole::AssistantOrganizer);
    }

    /**
     * Co-organizer+ can edit group settings.
     */
    public function editSettings(User $user, Group $group): bool
    {
        return $this->hasRole($user, $group, GroupRole::CoOrganizer);
    }

    /**
     * Co-organizer+ can manage leadership roles.
     */
    public function manageLeadership(User $user, Group $group): bool
    {
        return $this->hasRole($user, $group, GroupRole::CoOrganizer);
    }

    /**
     * Co-organizer+ can view group analytics.
     */
    public function viewAnalytics(User $user, Group $group): bool
    {
        return $this->hasRole($user, $group, GroupRole::CoOrganizer);
    }

    /**
     * Only the organizer can delete the group.
     */
    public function delete(User $user, Group $group): bool
    {
        return $this->hasRole($user, $group, GroupRole::Organizer);
    }

    /**
     * Only the organizer can transfer ownership.
     */
    public function transferOwnership(User $user, Group $group): bool
    {
        return $this->hasRole($user, $group, GroupRole::Organizer);
    }
}
