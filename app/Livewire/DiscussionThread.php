<?php

namespace App\Livewire;

use App\Models\Discussion;
use App\Models\DiscussionReply;
use App\Models\User;
use App\Notifications\NewDiscussionReply;
use App\Services\MarkdownService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Livewire\WithPagination;

class DiscussionThread extends Component
{
    use WithPagination;

    public Discussion $discussion;

    public string $body = '';

    public function mount(Discussion $discussion): void
    {
        $this->discussion = $discussion;
    }

    public function addReply(MarkdownService $markdown): void
    {
        Gate::authorize('reply', $this->discussion);

        $this->validate([
            'body' => ['required', 'string', 'max:10000'],
        ]);

        $reply = DiscussionReply::create([
            'discussion_id' => $this->discussion->id,
            'user_id' => Auth::id(),
            'body' => $this->body,
            'body_html' => $markdown->render($this->body),
        ]);

        $this->discussion->update([
            'last_activity_at' => now(),
        ]);

        $this->body = '';

        $this->notifyReply($reply);
    }

    public function deleteReply(int $replyId): void
    {
        $reply = DiscussionReply::findOrFail($replyId);

        Gate::authorize('deleteReply', $reply);

        $reply->delete();
    }

    public function render(): View
    {
        $replies = $this->discussion->replies()
            ->with('user')
            ->oldest()
            ->paginate(20);

        return view('livewire.discussion-thread', [
            'replies' => $replies,
        ]);
    }

    private function notifyReply(DiscussionReply $reply): void
    {
        $authorId = $this->discussion->user_id;

        $previousReplierIds = DiscussionReply::where('discussion_id', $this->discussion->id)
            ->where('id', '!=', $reply->id)
            ->distinct()
            ->pluck('user_id')
            ->toArray();

        $recipientIds = collect(array_merge([$authorId], $previousReplierIds))
            ->unique()
            ->reject(fn (int $id): bool => $id === $reply->user_id)
            ->values();

        $recipients = User::whereIn('id', $recipientIds)->get();

        foreach ($recipients as $recipient) {
            $recipient->notify(new NewDiscussionReply($reply, $this->discussion));
        }
    }
}
