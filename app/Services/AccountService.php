<?php

namespace App\Services;

use App\Enums\GroupRole;
use App\Models\User;
use InvalidArgumentException;

class AccountService
{
    /**
     * Soft delete a user account.
     */
    public function deleteAccount(User $user): void
    {
        if ($user->trashed()) {
            throw new InvalidArgumentException('Account is already deleted.');
        }

        // Check if user is the organizer of any active groups
        $activeGroupCount = $user->organizedGroups()
            ->where('is_active', true)
            ->count();

        if ($activeGroupCount > 0) {
            throw new InvalidArgumentException('You must transfer ownership of your groups before deleting your account.');
        }

        $user->delete();
    }

    /**
     * Export all user data as a JSON string.
     */
    public function exportData(User $user): string
    {
        $data = [
            'profile' => [
                'name' => $user->name,
                'email' => $user->email,
                'bio' => $user->bio,
                'location' => $user->location,
                'timezone' => $user->timezone,
                'looking_for' => $user->looking_for,
                'profile_visibility' => $user->profile_visibility?->value,
                'created_at' => $user->created_at?->toIso8601String(),
                'last_active_at' => $user->last_active_at?->toIso8601String(),
            ],
            'groups' => $user->groups()->get()->map(fn ($group) => [
                'name' => $group->name,
                'role' => $group->pivot->role instanceof GroupRole
                    ? $group->pivot->role->value
                    : (string) $group->pivot->role,
                'joined_at' => $group->pivot->joined_at?->toIso8601String(),
            ])->toArray(),
            'rsvps' => $user->rsvps()->with('event:id,name')->get()->map(fn ($rsvp) => [
                'event' => $rsvp->event?->name,
                'status' => $rsvp->status->value,
                'guest_count' => $rsvp->guest_count,
                'checked_in' => $rsvp->checked_in,
            ])->toArray(),
        ];

        return json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    /**
     * Suspend a user account.
     */
    public function suspendAccount(User $user, string $reason): void
    {
        if ($user->is_suspended) {
            throw new InvalidArgumentException('Account is already suspended.');
        }

        $user->update([
            'is_suspended' => true,
            'suspended_at' => now(),
            'suspended_reason' => $reason,
        ]);
    }

    /**
     * Unsuspend a user account.
     */
    public function unsuspendAccount(User $user): void
    {
        if (! $user->is_suspended) {
            throw new InvalidArgumentException('Account is not suspended.');
        }

        $user->update([
            'is_suspended' => false,
            'suspended_at' => null,
            'suspended_reason' => null,
        ]);
    }
}
