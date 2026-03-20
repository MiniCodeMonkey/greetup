<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\MergeInterestRequest;
use App\Http\Requests\Admin\StoreInterestRequest;
use App\Http\Requests\Admin\UpdateInterestRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Spatie\Tags\Tag;

class AdminInterestController extends Controller
{
    public function index(Request $request): View
    {
        $query = Tag::query()->where('type', 'interest');

        if ($search = $request->input('search')) {
            $query->where('name->en', 'like', "%{$search}%");
        }

        $interests = $query->orderBy('name->en')
            ->get()
            ->map(function (Tag $tag) {
                $tag->usage_count = DB::table('taggables')
                    ->where('tag_id', $tag->id)
                    ->count();

                return $tag;
            });

        $seoTitle = 'Admin: Interests — '.config('app.name', 'Greetup');

        return view('admin.interests.index', compact('interests', 'seoTitle'));
    }

    public function create(): View
    {
        $seoTitle = 'Admin: Create Interest — '.config('app.name', 'Greetup');

        return view('admin.interests.create', compact('seoTitle'));
    }

    public function store(StoreInterestRequest $request): RedirectResponse
    {
        Tag::findOrCreate($request->validated('name'), 'interest');

        return redirect()->route('admin.interests.index')
            ->with('success', 'Interest created successfully.');
    }

    public function edit(Tag $interest): View
    {
        $seoTitle = "Admin: Edit {$interest->name} — ".config('app.name', 'Greetup');

        return view('admin.interests.edit', compact('interest', 'seoTitle'));
    }

    public function update(UpdateInterestRequest $request, Tag $interest): RedirectResponse
    {
        $interest->name = $request->validated('name');
        $interest->save();

        return redirect()->route('admin.interests.index')
            ->with('success', 'Interest updated successfully.');
    }

    public function destroy(Tag $interest): RedirectResponse
    {
        $interestName = $interest->name;
        $interest->delete();

        return redirect()->route('admin.interests.index')
            ->with('success', "Interest \"{$interestName}\" has been deleted.");
    }

    public function merge(MergeInterestRequest $request, Tag $interest): RedirectResponse
    {
        $targetId = $request->validated('target_id');
        $target = Tag::findOrFail($targetId);
        $sourceName = $interest->name;
        $targetName = $target->name;

        DB::transaction(function () use ($interest, $target) {
            // Get existing taggable records on the target to avoid duplicates
            $existingTaggables = DB::table('taggables')
                ->where('tag_id', $target->id)
                ->get(['taggable_type', 'taggable_id'])
                ->map(fn ($row) => $row->taggable_type.'|'.$row->taggable_id)
                ->toArray();

            // Reassign source taggables to target (skip duplicates)
            $sourceTaggables = DB::table('taggables')
                ->where('tag_id', $interest->id)
                ->get();

            foreach ($sourceTaggables as $taggable) {
                $key = $taggable->taggable_type.'|'.$taggable->taggable_id;
                if (! in_array($key, $existingTaggables)) {
                    DB::table('taggables')
                        ->where('tag_id', $interest->id)
                        ->where('taggable_type', $taggable->taggable_type)
                        ->where('taggable_id', $taggable->taggable_id)
                        ->update(['tag_id' => $target->id]);
                } else {
                    // Delete duplicate
                    DB::table('taggables')
                        ->where('tag_id', $interest->id)
                        ->where('taggable_type', $taggable->taggable_type)
                        ->where('taggable_id', $taggable->taggable_id)
                        ->delete();
                }
            }

            // Delete the source tag
            $interest->delete();
        });

        return redirect()->route('admin.interests.index')
            ->with('success', "Interest \"{$sourceName}\" has been merged into \"{$targetName}\".");
    }
}
