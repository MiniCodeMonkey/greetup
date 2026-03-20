<?php

namespace App\Services;

use App\Enums\GroupRole;
use App\Enums\JoinRequestStatus;
use App\Models\Group;
use App\Models\GroupJoinRequest;
use App\Models\GroupMembershipAnswer;
use App\Models\User;
use App\Notifications\JoinRequestApproved;
use App\Notifications\JoinRequestDenied;
use App\Notifications\JoinRequestReceived;
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
     *
     * @param  array<int, string>  $answers  Keyed by question_id
     */
    public function requestToJoin(Group $group, User $user, array $answers = []): GroupJoinRequest
    {
        if ($this->isMember($group, $user)) {
            throw new InvalidArgumentException('User is already a member of this group.');
        }

        if (! $group->requires_approval) {
            throw new InvalidArgumentException('This group does not require approval. Use joinGroup() instead.');
        }

        $joinRequest = GroupJoinRequest::query()
            ->where('group_id', $group->id)
            ->where('user_id', $user->id)
            ->first();

        if ($joinRequest !== null) {
            $joinRequest->update([
                'status' => JoinRequestStatus::Pending,
                'reviewed_by' => null,
                'reviewed_at' => null,
                'denial_reason' => null,
            ]);
        } else {
            $joinRequest = GroupJoinRequest::create([
                'group_id' => $group->id,
                'user_id' => $user->id,
                'status' => JoinRequestStatus::Pending,
            ]);
        }

        foreach ($answers as $questionId => $answer) {
            GroupMembershipAnswer::query()->updateOrCreate(
                ['question_id' => $questionId, 'user_id' => $user->id],
                ['answer' => $answer],
            );
        }

        $this->notifyLeadership($group, new JoinRequestReceived($joinRequest));

        return $joinRequest;
    }

    /**
     * Notify organizer and assistant+ members of the group.
     */
    private function notifyLeadership(Group $group, object $notification): void
    {
        $leaders = $group->members()
            ->where('group_members.is_banned', false)
            ->whereIn('group_members.role', [
                GroupRole::Organizer->value,
                GroupRole::CoOrganizer->value,
                GroupRole::AssistantOrganizer->value,
            ])
            ->get();

        foreach ($leaders as $leader) {
            $leader->notify($notification);
        }
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

        $request->user->notify(new JoinRequestApproved($request));

        if ($group->welcome_message) {
            $request->user->notify(new WelcomeToGroup($group));
        }
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

        $request->user->notify(new JoinRequestDenied($request));
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
