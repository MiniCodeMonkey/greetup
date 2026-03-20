<?php

namespace App\Policies;

use App\Enums\ProfileVisibility;
use App\Models\User;

class UserPolicy
{
    /**
     * Determine whether the user can view the profile.
     *
     * Public profiles are visible to everyone. Members-only profiles
     * are only visible to the profile owner or users who share at least one group.
     */
    public function view(User $viewer, User $profileOwner): bool
    {
        if ($viewer->id === $profileOwner->id) {
            return true;
        }

        if ($profileOwner->profile_visibility === ProfileVisibility::Public) {
            return true;
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
}
