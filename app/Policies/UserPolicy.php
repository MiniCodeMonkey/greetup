<?php

namespace App\Policies;

use App\Enums\ProfileVisibility;
use App\Models\Block;
use App\Models\User;

class UserPolicy
{
    /**
     * Determine whether the user can view the profile.
     *
     * Public profiles are visible to everyone. Members-only profiles
     * are only visible to the profile owner or users who share at least one group.
     * Blocked users cannot view the blocker's profile.
     */
    public function view(?User $viewer, User $profileOwner): bool
    {
        if ($viewer && $viewer->id === $profileOwner->id) {
            return true;
        }

        if ($viewer && $this->isBlocked($viewer, $profileOwner)) {
            return false;
        }

        if ($profileOwner->profile_visibility === ProfileVisibility::Public) {
            return true;
        }

        if (! $viewer) {
            return false;
        }

        return $this->sharesGroup($viewer, $profileOwner);
    }

    /**
     * Check if two users share at least one group.
     */
    private function sharesGroup(User $userA, User $userB): bool
    {
        return $userA->groups()
            ->whereIn('groups.id', $userB->groups()->select('groups.id'))
            ->exists();
    }

    /**
     * Check if the profile owner has blocked the viewer.
     */
    private function isBlocked(User $viewer, User $profileOwner): bool
    {
        return Block::where('blocker_id', $profileOwner->id)
            ->where('blocked_id', $viewer->id)
            ->exists();
    }
}
