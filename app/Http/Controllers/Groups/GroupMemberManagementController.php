<?php

namespace App\Http\Controllers\Groups;

use App\Enums\AttendanceResult;
use App\Http\Controllers\Controller;
use App\Http\Requests\Groups\BanMemberRequest;
use App\Http\Requests\Groups\RemoveMemberRequest;
use App\Models\Group;
use App\Models\User;
use App\Services\ExportService;
use App\Services\GroupMembershipService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class GroupMemberManagementController extends Controller
{
    /**
     * Display the member management page with search/filter and pagination.
     */
    public function index(Request $request, Group $group): View
    {
        $search = $request->input('search');

        $query = $group->members()
            ->withPivot('role', 'joined_at', 'is_banned')
            ->where('group_members.is_banned', false)
            ->orderBy('group_members.joined_at', 'desc');

        if ($search) {
            $query->where('name', 'like', "%{$search}%");
        }

        $members = $query->paginate(20)->withQueryString();

        $eventIds = $group->events()->pluck('id');

        $memberStats = [];

        if ($eventIds->isNotEmpty()) {
            foreach ($members as $member) {
                $memberStats[$member->id] = [
                    'attended' => $member->rsvps()
                        ->whereIn('event_id', $eventIds)
                        ->where('attended', AttendanceResult::Attended)
                        ->count(),
                    'no_shows' => $member->rsvps()
                        ->whereIn('event_id', $eventIds)
                        ->where('attended', AttendanceResult::NoShow)
                        ->count(),
                ];
            }
        }

        return view('groups.manage.members', [
            'group' => $group,
            'members' => $members,
            'memberStats' => $memberStats,
            'search' => $search,
        ]);
    }

    /**
     * Remove a member from the group.
     */
    public function remove(RemoveMemberRequest $request, Group $group, User $user, GroupMembershipService $service): RedirectResponse
    {
        $service->removeMember($group, $user, $request->user(), $request->validated()['reason'] ?? null);

        return redirect()->route('groups.manage.members', $group)
            ->with('status', "{$user->name} has been removed from the group.");
    }

    /**
     * Ban a member from the group.
     */
    public function ban(BanMemberRequest $request, Group $group, User $user, GroupMembershipService $service): RedirectResponse
    {
        $service->banMember($group, $user, $request->user(), $request->validated()['reason']);

        return redirect()->route('groups.manage.members', $group)
            ->with('status', "{$user->name} has been banned from the group.");
    }

    /**
     * Unban a member.
     */
    public function unban(Request $request, Group $group, User $user, GroupMembershipService $service): RedirectResponse
    {
        $service->unbanMember($group, $user);

        return redirect()->route('groups.manage.members', $group)
            ->with('status', "{$user->name} has been unbanned.");
    }

    /**
     * Export member list as CSV.
     */
    public function export(Group $group, ExportService $exportService): StreamedResponse
    {
        $csv = $exportService->exportMembers($group);

        return response()->streamDownload(function () use ($csv) {
            echo $csv;
        }, "{$group->slug}-members.csv", [
            'Content-Type' => 'text/csv',
        ]);
    }
}
