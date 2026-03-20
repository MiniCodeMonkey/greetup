<?php

namespace App\Http\Controllers\Groups;

use App\Enums\GroupRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Groups\CreateGroupRequest;
use App\Models\Group;
use App\Services\MarkdownService;
use Illuminate\Http\RedirectResponse;
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
     * Display the group page.
     */
    public function show(Group $group): View
    {
        return view('groups.show', ['group' => $group]);
    }
}
