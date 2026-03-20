<?php

namespace App\Http\Controllers\Groups;

use App\Http\Controllers\Controller;
use App\Http\Requests\Groups\TransferOwnershipRequest;
use App\Models\Group;
use App\Models\User;
use App\Services\GroupMembershipService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class OwnershipTransferController extends Controller
{
    /**
     * Show the ownership transfer form.
     */
    public function edit(Group $group): View
    {
        $coOrganizers = $group->members()
            ->wherePivot('role', 'co_organizer')
            ->wherePivot('is_banned', false)
            ->orderBy('name')
            ->get();

        return view('groups.manage.transfer', [
            'group' => $group,
            'coOrganizers' => $coOrganizers,
        ]);
    }

    /**
     * Transfer group ownership to a co-organizer.
     */
    public function update(TransferOwnershipRequest $request, Group $group, GroupMembershipService $membershipService): RedirectResponse
    {
        $newOwnerId = (int) $request->validated('new_owner_id');
        $newOwner = User::findOrFail($newOwnerId);

        $membershipService->transferOwnership($group, $newOwner);

        return redirect()->route('groups.show', $group)
            ->with('status', 'Group ownership has been transferred successfully.');
    }
}
