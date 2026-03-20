<?php

namespace App\Http\Controllers\Discussions;

use App\Http\Controllers\Controller;
use App\Http\Requests\Discussions\CreateDiscussionRequest;
use App\Models\Discussion;
use App\Models\Group;
use App\Notifications\NewDiscussion;
use App\Services\MarkdownService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class DiscussionController extends Controller
{
    /**
     * Show a discussion with its replies.
     */
    public function show(Group $group, Discussion $discussion): View
    {
        $discussion->load('author');

        return view('discussions.show', [
            'group' => $group,
            'discussion' => $discussion,
        ]);
    }

    /**
     * Show the discussion creation form.
     */
    public function create(Group $group): View
    {
        return view('discussions.create', [
            'group' => $group,
        ]);
    }

    /**
     * Store a newly created discussion.
     */
    public function store(CreateDiscussionRequest $request, Group $group, MarkdownService $markdownService): RedirectResponse
    {
        $validated = $request->validated();

        $discussion = Discussion::create([
            'group_id' => $group->id,
            'user_id' => $request->user()->id,
            'title' => $validated['title'],
            'body' => $validated['body'],
            'body_html' => $markdownService->render($validated['body']),
            'last_activity_at' => now(),
        ]);

        $members = $group->members()
            ->where('group_members.is_banned', false)
            ->where('user_id', '!=', $request->user()->id)
            ->get();

        foreach ($members as $member) {
            $member->notify(new NewDiscussion($discussion, $group));
        }

        return redirect()->route('groups.show', ['group' => $group->slug, 'tab' => 'discussions'])
            ->with('status', 'Discussion created successfully!');
    }
}
