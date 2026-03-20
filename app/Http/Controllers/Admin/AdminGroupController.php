<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Group;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminGroupController extends Controller
{
    public function index(Request $request): View
    {
        $query = Group::query()->with('organizer')->withCount(['members', 'events']);

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('location', 'like', "%{$search}%");
            });
        }

        if ($request->input('visibility')) {
            $query->where('visibility', $request->input('visibility'));
        }

        $groups = $query->latest()->paginate(25)->withQueryString();

        $seoTitle = 'Admin: Groups — '.config('app.name', 'Greetup');

        return view('admin.groups.index', compact('groups', 'seoTitle'));
    }

    public function show(Group $group): View
    {
        $group->load('organizer')->loadCount(['members', 'events']);

        $seoTitle = "Admin: {$group->name} — ".config('app.name', 'Greetup');

        return view('admin.groups.show', compact('group', 'seoTitle'));
    }

    public function destroy(Group $group): RedirectResponse
    {
        $groupName = $group->name;
        $group->forceDelete();

        return redirect()->route('admin.groups.index')
            ->with('success', "Group {$groupName} has been deleted.");
    }
}
