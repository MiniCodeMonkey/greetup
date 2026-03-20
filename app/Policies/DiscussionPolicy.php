<?php

namespace App\Policies;

use App\Enums\GroupRole;
use App\Models\Discussion;
use App\Models\DiscussionReply;
use App\Models\Group;
use App\Models\User;

class DiscussionPolicy
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
     * Any group member can create a discussion.
     */
    public function create(User $user, Group $group): bool
    {
        return $this->isActiveMember($user, $group);
    }

    /**
     * Any group member can reply to a discussion (unless locked).
     */
    public function reply(User $user, Discussion $discussion): bool
    {
        if ($discussion->is_locked) {
            return false;
        }

        return $this->isActiveMember($user, $discussion->group);
    }

    /**
     * Co-organizer+ can pin/unpin discussions.
     */
    public function pin(User $user, Discussion $discussion): bool
    {
        return $this->hasGroupRole($user, $discussion->group, GroupRole::CoOrganizer);
    }

    /**
     * Co-organizer+ can lock/unlock discussions.
     */
    public function lock(User $user, Discussion $discussion): bool
    {
        return $this->hasGroupRole($user, $discussion->group, GroupRole::CoOrganizer);
    }

    /**
     * Co-organizer+ can delete any discussion.
     */
    public function delete(User $user, Discussion $discussion): bool
    {
        return $this->hasGroupRole($user, $discussion->group, GroupRole::CoOrganizer);
    }

    /**
     * Authors can delete their own replies; co-organizer+ can delete any reply.
     */
    public function deleteReply(User $user, DiscussionReply $reply): bool
    {
        if ($user->is_suspended) {
            return false;
        }

        if ($this->hasGroupRole($user, $reply->discussion->group, GroupRole::CoOrganizer)) {
            return true;
        }

        if ($this->isActiveMember($user, $reply->discussion->group) && $reply->user_id === $user->id) {
            return true;
        }

        return false;
    }
}
