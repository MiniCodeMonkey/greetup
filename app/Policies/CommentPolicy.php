<?php

namespace App\Policies;

use App\Enums\GroupRole;
use App\Models\Comment;
use App\Models\Event;
use App\Models\User;

class CommentPolicy
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
     * Any group member can create a comment if comments are enabled.
     */
    public function create(User $user, Event $event): bool
    {
        if ($user->is_suspended) {
            return false;
        }

        if (! $event->is_comments_enabled) {
            return false;
        }

        $membership = $this->getMembership($user, $event);

        return $membership !== null && ! $membership['is_banned'];
    }

    /**
     * Author or co_organizer+ can soft delete a comment.
     */
    public function delete(User $user, Comment $comment): bool
    {
        if ($user->is_suspended) {
            return false;
        }

        if ($comment->user_id === $user->id) {
            return true;
        }

        $event = $comment->event;
        $membership = $this->getMembership($user, $event);

        if (! $membership || $membership['is_banned']) {
            return false;
        }

        return $membership['role']->isAtLeast(GroupRole::CoOrganizer);
    }
}
