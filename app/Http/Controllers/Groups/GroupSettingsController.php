<?php

namespace App\Http\Controllers\Groups;

use App\Http\Controllers\Controller;
use App\Http\Requests\Groups\UpdateGroupSettingsRequest;
use App\Models\Group;
use App\Services\MarkdownService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class GroupSettingsController extends Controller
{
    /**
     * Show the group settings form.
     */
    public function edit(Group $group): View
    {
        $group->load(['membershipQuestions' => function ($query) {
            $query->orderBy('sort_order');
        }]);

        $topics = $group->tagsWithType('topic')->pluck('name')->toArray();

        return view('groups.manage.settings', [
            'group' => $group,
            'topics' => $topics,
        ]);
    }

    /**
     * Update the group settings.
     */
    public function update(UpdateGroupSettingsRequest $request, Group $group, MarkdownService $markdownService): RedirectResponse
    {
        $validated = $request->validated();

        $group->update([
            'name' => $validated['name'],
            'slug' => $validated['slug'],
            'description' => $validated['description'] ?? null,
            'description_html' => isset($validated['description'])
                ? $markdownService->render($validated['description'])
                : null,
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

        $group->syncTagsWithType($validated['topics'] ?? [], 'topic');

        $this->syncMembershipQuestions($group, $validated['membership_questions'] ?? []);

        return redirect()->route('groups.manage.settings', $group)
            ->with('status', 'Group settings updated successfully.');
    }

    /**
     * Sync membership questions: update existing, create new, delete removed.
     *
     * @param  array<int, array{id?: int, question: string, is_required?: bool}>  $questions
     */
    private function syncMembershipQuestions(Group $group, array $questions): void
    {
        $existingIds = $group->membershipQuestions()->pluck('id')->toArray();
        $submittedIds = [];

        foreach ($questions as $index => $questionData) {
            if (! empty($questionData['id']) && in_array($questionData['id'], $existingIds)) {
                $group->membershipQuestions()
                    ->where('id', $questionData['id'])
                    ->update([
                        'question' => $questionData['question'],
                        'is_required' => $questionData['is_required'] ?? true,
                        'sort_order' => $index,
                    ]);
                $submittedIds[] = $questionData['id'];
            } else {
                $created = $group->membershipQuestions()->create([
                    'question' => $questionData['question'],
                    'is_required' => $questionData['is_required'] ?? true,
                    'sort_order' => $index,
                ]);
                $submittedIds[] = $created->id;
            }
        }

        $idsToDelete = array_diff($existingIds, $submittedIds);
        if (! empty($idsToDelete)) {
            $group->membershipQuestions()->whereIn('id', $idsToDelete)->delete();
        }
    }
}
