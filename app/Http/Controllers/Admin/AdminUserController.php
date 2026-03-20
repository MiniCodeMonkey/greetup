<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SuspendUserRequest;
use App\Models\User;
use App\Notifications\AccountSuspended;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminUserController extends Controller
{
    public function index(Request $request): View
    {
        $query = User::query()->withCount(['groups', 'rsvps']);

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->input('suspended') === '1') {
            $query->where('is_suspended', true);
        }

        $users = $query->latest()->paginate(25)->withQueryString();

        $seoTitle = 'Admin: Users — '.config('app.name', 'Greetup');

        return view('admin.users.index', compact('users', 'seoTitle'));
    }

    public function show(User $user): View
    {
        $user->loadCount(['groups', 'rsvps']);
        $groups = $user->groups()->latest('group_members.created_at')->get();
        $rsvps = $user->rsvps()->with(['event.group'])->latest()->limit(25)->get();

        $seoTitle = "Admin: {$user->name} — ".config('app.name', 'Greetup');

        return view('admin.users.show', compact('user', 'groups', 'rsvps', 'seoTitle'));
    }

    public function suspend(SuspendUserRequest $request, User $user): RedirectResponse
    {
        $user->update([
            'is_suspended' => true,
            'suspended_at' => now(),
            'suspended_reason' => $request->validated('reason'),
        ]);

        $user->notify(new AccountSuspended($request->validated('reason')));

        return redirect()->route('admin.users.show', $user)
            ->with('success', "User {$user->name} has been suspended.");
    }

    public function unsuspend(User $user): RedirectResponse
    {
        $user->update([
            'is_suspended' => false,
            'suspended_at' => null,
            'suspended_reason' => null,
        ]);

        return redirect()->route('admin.users.show', $user)
            ->with('success', "User {$user->name} has been unsuspended.");
    }

    public function destroy(User $user): RedirectResponse
    {
        $userName = $user->name;
        $user->forceDelete();

        return redirect()->route('admin.users.index')
            ->with('success', "User {$userName} has been deleted.");
    }
}
