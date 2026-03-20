<?php

namespace App\Services;

use App\Enums\GroupRole;
use App\Enums\JoinRequestStatus;
use App\Models\Group;
use App\Models\GroupJoinRequest;
use App\Models\User;
use App\Notifications\WelcomeToGroup;
use InvalidArgumentException;

class GroupMembershipService
{
    /**
     * Join an open group immediately.
     */
    public function joinGroup(Group $group, User $user): void
    {
        if ($this->isBanned($group, $user)) {
            throw new InvalidArgumentException('User is banned from this group.');
        }

        if ($this->isMember($group, $user)) {
            throw new InvalidArgumentException('User is already a member of this group.');
        }

        if ($group->requires_approval) {
            throw new InvalidArgumentException('This group requires approval to join.');
        }

        if ($group->max_members !== null && $group->members()->count() >= $group->max_members) {
            throw new InvalidArgumentException('This group has reached its member limit.');
        }

        $group->members()->attach($user, [
            'role' => GroupRole::Member->value,
            'joined_at' => now(),
        ]);

        $user->notify(new WelcomeToGroup($group));
    }

    /**
     * Submit a join request for an approval-required group.
     */
    public function requestToJoin(Group $group, User $user): GroupJoinRequest
    {
        if ($this->isMember($group, $user)) {
            throw new InvalidArgumentException('User is already a member of this group.');
        }

        if (! $group->requires_approval) {
            throw new InvalidArgumentException('This group does not require approval. Use joinGroup() instead.');
        }

        $existingRequest = GroupJoinRequest::query()
            ->where('group_id', $group->id)
            ->where('user_id', $user->id)
            ->where('status', JoinRequestStatus::Pending)
            ->first();

        if ($existingRequest !== null) {
            throw new InvalidArgumentException('User already has a pending join request.');
        }

        return GroupJoinRequest::create([
            'group_id' => $group->id,
            'user_id' => $user->id,
            'status' => JoinRequestStatus::Pending,
        ]);
    }

    /**
     * Approve a pending join request.
     */
    public function approveRequest(GroupJoinRequest $request, User $reviewer): void
    {
        if ($request->status !== JoinRequestStatus::Pending) {
            throw new InvalidArgumentException('Only pending requests can be approved.');
        }

        $group = $request->group;

        if ($group->max_members !== null && $group->members()->count() >= $group->max_members) {
            throw new InvalidArgumentException('This group has reached its member limit.');
        }

        $request->update([
            'status' => JoinRequestStatus::Approved,
            'reviewed_by' => $reviewer->id,
            'reviewed_at' => now(),
        ]);

        $group->members()->attach($request->user_id, [
            'role' => GroupRole::Member->value,
            'joined_at' => now(),
        ]);
    }

    /**
     * Deny a pending join request.
     */
    public function denyRequest(GroupJoinRequest $request, User $reviewer, ?string $reason = null): void
    {
        if ($request->status !== JoinRequestStatus::Pending) {
            throw new InvalidArgumentException('Only pending requests can be denied.');
        }

        $request->update([
            'status' => JoinRequestStatus::Denied,
            'reviewed_by' => $reviewer->id,
            'reviewed_at' => now(),
            'denial_reason' => $reason,
        ]);
    }

    /**
     * Leave a group.
     */
    public function leaveGroup(Group $group, User $user): void
    {
        if (! $this->isMember($group, $user)) {
            throw new InvalidArgumentException('User is not a member of this group.');
        }

        if ($group->organizer_id === $user->id) {
            throw new InvalidArgumentException('The group organizer must transfer ownership before leaving.');
        }

        $group->members()->detach($user);
    }

    /**
     * Change a member's role within a group.
     */
    public function changeRole(Group $group, User $user, GroupRole $newRole): void
    {
        if (! $this->isMember($group, $user)) {
            throw new InvalidArgumentException('User is not a member of this group.');
        }

        $group->members()->updateExistingPivot($user->id, [
            'role' => $newRole->value,
        ]);
    }

    /**
     * Check if a user is a member of a group.
     */
    public function isMember(Group $group, User $user): bool
    {
        return $group->members()->where('user_id', $user->id)->exists();
    }

    /**
     * Check if a user is banned from a group.
     */
    public function isBanned(Group $group, User $user): bool
    {
        return $group->members()
            ->where('user_id', $user->id)
            ->where('is_banned', true)
            ->exists();
    }
}
