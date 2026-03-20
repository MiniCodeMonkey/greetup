<?php

namespace App\Http\Controllers\Groups;

use App\Enums\GroupRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Groups\ChangeLeadershipRoleRequest;
use App\Models\Group;
use App\Models\User;
use App\Notifications\RoleChanged;
use App\Services\GroupMembershipService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LeadershipTeamController extends Controller
{
    /**
     * Display the leadership team management page.
     */
    public function index(Request $request, Group $group): View
    {
        $leadershipMembers = $group->members()
            ->withPivot('role', 'joined_at')
            ->where('group_members.is_banned', false)
            ->whereIn('group_members.role', [
                GroupRole::Organizer->value,
                GroupRole::CoOrganizer->value,
                GroupRole::AssistantOrganizer->value,
                GroupRole::EventOrganizer->value,
            ])
            ->orderByRaw('FIELD(group_members.role, ?, ?, ?, ?)', [
                GroupRole::Organizer->value,
                GroupRole::CoOrganizer->value,
                GroupRole::AssistantOrganizer->value,
                GroupRole::EventOrganizer->value,
            ])
            ->get();

        $regularMembers = $group->members()
            ->withPivot('role', 'joined_at')
            ->where('group_members.is_banned', false)
            ->where('group_members.role', GroupRole::Member->value)
            ->orderBy('name')
            ->get();

        $currentUserRole = $group->members()
            ->where('user_id', $request->user()->id)
            ->first()
            ?->pivot->role;

        $isOrganizer = $currentUserRole instanceof GroupRole
            ? $currentUserRole === GroupRole::Organizer
            : $currentUserRole === GroupRole::Organizer->value;

        return view('groups.manage.team', [
            'group' => $group,
            'leadershipMembers' => $leadershipMembers,
            'regularMembers' => $regularMembers,
            'isOrganizer' => $isOrganizer,
        ]);
    }

    /**
     * Change a member's role (promote or demote).
     */
    public function update(
        ChangeLeadershipRoleRequest $request,
        Group $group,
        User $user,
        GroupMembershipService $service,
    ): RedirectResponse {
        $newRole = GroupRole::from($request->validated()['role']);

        $currentMember = $group->members()
            ->where('user_id', $user->id)
            ->first();

        if (! $currentMember) {
            abort(404);
        }

        $currentRole = $currentMember->pivot->role instanceof GroupRole
            ? $currentMember->pivot->role
            : GroupRole::from($currentMember->pivot->role);

        // Cannot change the primary organizer's role
        if ($currentRole === GroupRole::Organizer) {
            abort(403, 'Cannot change the primary organizer\'s role.');
        }

        $actorMember = $group->members()
            ->where('user_id', $request->user()->id)
            ->first();

        $actorRole = $actorMember->pivot->role instanceof GroupRole
            ? $actorMember->pivot->role
            : GroupRole::from($actorMember->pivot->role);

        // Co-organizers cannot promote to co_organizer or demote other co_organizers
        if ($actorRole === GroupRole::CoOrganizer) {
            if ($newRole === GroupRole::CoOrganizer) {
                abort(403, 'Only the primary organizer can promote to co-organizer.');
            }

            if ($currentRole === GroupRole::CoOrganizer) {
                abort(403, 'Only the primary organizer can demote a co-organizer.');
            }
        }

        if ($currentRole === $newRole) {
            return redirect()->route('groups.manage.team', $group)
                ->with('status', "{$user->name} already has that role.");
        }

        $oldRole = $currentRole;
        $service->changeRole($group, $user, $newRole);

        $user->notify(new RoleChanged($group, $oldRole, $newRole));

        $newLabel = ucfirst(str_replace('_', ' ', $newRole->value));

        return redirect()->route('groups.manage.team', $group)
            ->with('status', "{$user->name}'s role has been changed to {$newLabel}.");
    }
}
