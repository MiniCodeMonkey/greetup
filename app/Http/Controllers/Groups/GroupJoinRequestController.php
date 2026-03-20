<?php

namespace App\Http\Controllers\Groups;

use App\Enums\JoinRequestStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Groups\HandleJoinRequestRequest;
use App\Models\Group;
use App\Models\GroupJoinRequest;
use App\Services\GroupMembershipService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class GroupJoinRequestController extends Controller
{
    /**
     * Display pending join requests for the group.
     */
    public function index(Group $group): View
    {
        $requests = $group->joinRequests()
            ->with('user')
            ->where('status', JoinRequestStatus::Pending)
            ->orderBy('created_at', 'asc')
            ->paginate(20);

        return view('groups.manage.requests', [
            'group' => $group,
            'requests' => $requests,
        ]);
    }

    /**
     * Approve a pending join request.
     */
    public function approve(HandleJoinRequestRequest $request, Group $group, GroupJoinRequest $joinRequest, GroupMembershipService $service): RedirectResponse
    {
        $service->approveRequest($joinRequest, $request->user());

        return redirect()->route('groups.manage.requests', $group)
            ->with('status', "{$joinRequest->user->name}'s request has been approved.");
    }

    /**
     * Deny a pending join request.
     */
    public function deny(HandleJoinRequestRequest $request, Group $group, GroupJoinRequest $joinRequest, GroupMembershipService $service): RedirectResponse
    {
        $service->denyRequest($joinRequest, $request->user(), $request->validated()['reason'] ?? null);

        return redirect()->route('groups.manage.requests', $group)
            ->with('status', "{$joinRequest->user->name}'s request has been denied.");
    }
}
