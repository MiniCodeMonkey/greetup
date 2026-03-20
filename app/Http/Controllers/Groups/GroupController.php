<?php

namespace App\Http\Controllers\Groups;

use App\Enums\GroupRole;
use App\Enums\GroupVisibility;
use App\Http\Controllers\Controller;
use App\Http\Requests\Groups\CreateGroupRequest;
use App\Models\Group;
use App\Services\GroupMembershipService;
use App\Services\MarkdownService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\View\View;

class GroupController extends Controller
{
    /**
     * Show the group creation form.
     */
    public function create(): View
    {
        return view('groups.create');
    }

    /**
     * Store a newly created group.
     */
    public function store(CreateGroupRequest $request, MarkdownService $markdownService): RedirectResponse
    {
        $validated = $request->validated();

        $group = Group::create([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'description_html' => isset($validated['description'])
                ? $markdownService->render($validated['description'])
                : null,
            'organizer_id' => $request->user()->id,
            'location' => $validated['location'] ?? null,
            'visibility' => $validated['visibility'],
            'requires_approval' => $validated['requires_approval'] ?? false,
            'max_members' => $validated['max_members'] ?? null,
            'welcome_message' => $validated['welcome_message'] ?? null,
        ]);

        if ($request->hasFile('cover_photo')) {
            $group->addMediaFromRequest('cover_photo')
                ->toMediaCollection('cover_photo');
        }

        if (! empty($validated['topics'])) {
            $group->syncTagsWithType($validated['topics'], 'topic');
        }

        if (! empty($validated['membership_questions'])) {
            foreach ($validated['membership_questions'] as $index => $questionData) {
                $group->membershipQuestions()->create([
                    'question' => $questionData['question'],
                    'is_required' => $questionData['is_required'] ?? true,
                    'sort_order' => $index,
                ]);
            }
        }

        $group->members()->attach($request->user()->id, [
            'role' => GroupRole::Organizer->value,
            'joined_at' => now(),
        ]);

        return redirect()->route('groups.show', $group)
            ->with('status', 'Group created successfully!');
    }

    /**
     * Join an open group.
     */
    public function join(Request $request, Group $group, GroupMembershipService $membershipService): RedirectResponse
    {
        Gate::authorize('join', $group);

        $membershipService->joinGroup($group, $request->user());

        return redirect()->route('groups.show', $group)
            ->with('status', "You've joined {$group->name}!");
    }

    /**
     * Display the group page.
     */
    public function show(Request $request, Group $group): View
    {
        $user = $request->user();
        $isMember = false;
        $membership = null;

        if ($user) {
            $membership = $group->members()->where('user_id', $user->id)->first()?->pivot;
            $isMember = $membership !== null && ! $membership->is_banned;
        }

        $isPrivate = $group->visibility === GroupVisibility::Private;

        $group->loadCount(['members' => function ($query) {
            $query->where('group_members.is_banned', false);
        }]);

        $group->load('organizer');

        $topics = $group->tagsWithType('topic');

        $memberAvatars = $group->members()
            ->where('group_members.is_banned', false)
            ->orderBy('group_members.joined_at')
            ->limit(5)
            ->get();

        $tab = $request->query('tab', 'upcoming');

        $upcomingEvents = collect();
        $pastEvents = collect();
        $discussions = collect();
        $allMembers = collect();
        $leadershipTeam = collect();

        if (! $isPrivate || $isMember) {
            if ($tab === 'upcoming') {
                $upcomingEvents = $group->events()
                    ->where('starts_at', '>=', now())
                    ->orderBy('starts_at')
                    ->limit(20)
                    ->get();
            }

            if ($tab === 'past') {
                $pastEvents = $group->events()
                    ->where('starts_at', '<', now())
                    ->orderByDesc('starts_at')
                    ->limit(20)
                    ->get();
            }

            if ($tab === 'discussions') {
                $discussions = $group->discussions()
                    ->with('author')
                    ->latest()
                    ->limit(20)
                    ->get();
            }

            if ($tab === 'members') {
                $allMembers = $group->members()
                    ->where('group_members.is_banned', false)
                    ->orderBy('group_members.joined_at')
                    ->get();
            }

            if ($tab === 'about') {
                $leadershipTeam = $group->members()
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
            }
        }

        $coverPhoto = $group->getFirstMediaUrl('cover_photo', 'header');

        $seoTitle = $group->name.' — '.config('app.name', 'Greetup');
        $seoDescription = $group->description
            ? Str::limit(strip_tags($group->description), 160)
            : null;
        $seoImage = $coverPhoto ?: null;

        return view('groups.show', [
            'group' => $group,
            'isMember' => $isMember,
            'membership' => $membership,
            'isPrivate' => $isPrivate,
            'topics' => $topics,
            'memberAvatars' => $memberAvatars,
            'tab' => $tab,
            'upcomingEvents' => $upcomingEvents,
            'pastEvents' => $pastEvents,
            'discussions' => $discussions,
            'allMembers' => $allMembers,
            'leadershipTeam' => $leadershipTeam,
            'coverPhoto' => $coverPhoto,
            'seoTitle' => $seoTitle,
            'seoDescription' => $seoDescription,
            'seoImage' => $seoImage,
        ]);
    }
}
