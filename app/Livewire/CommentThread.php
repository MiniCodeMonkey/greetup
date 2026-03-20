<?php

namespace App\Livewire;

use App\Enums\RsvpStatus;
use App\Models\Comment;
use App\Models\Event;
use App\Models\User;
use App\Notifications\EventCommentLiked;
use App\Notifications\EventCommentReply;
use App\Notifications\NewEventComment;
use App\Services\MarkdownService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Livewire\WithPagination;

class CommentThread extends Component
{
    use WithPagination;

    public Event $event;

    public string $body = '';

    public ?int $replyingTo = null;

    public string $replyBody = '';

    public function mount(Event $event): void
    {
        $this->event = $event;
    }

    public function addComment(MarkdownService $markdown): void
    {
        Gate::authorize('create', [Comment::class, $this->event]);

        $this->validate([
            'body' => ['required', 'string', 'max:5000'],
        ]);

        $comment = Comment::create([
            'event_id' => $this->event->id,
            'user_id' => Auth::id(),
            'body' => $this->body,
            'body_html' => $markdown->render($this->body),
        ]);

        $this->body = '';

        $this->notifyNewComment($comment);
    }

    public function startReply(int $commentId): void
    {
        $this->replyingTo = $commentId;
        $this->replyBody = '';
    }

    public function cancelReply(): void
    {
        $this->replyingTo = null;
        $this->replyBody = '';
    }

    public function addReply(MarkdownService $markdown): void
    {
        Gate::authorize('create', [Comment::class, $this->event]);

        $this->validate([
            'replyBody' => ['required', 'string', 'max:5000'],
        ]);

        $parent = Comment::findOrFail($this->replyingTo);

        $reply = Comment::create([
            'event_id' => $this->event->id,
            'user_id' => Auth::id(),
            'parent_id' => $parent->id,
            'body' => $this->replyBody,
            'body_html' => $markdown->render($this->replyBody),
        ]);

        $this->replyingTo = null;
        $this->replyBody = '';

        $this->notifyReply($reply, $parent);
    }

    public function toggleLike(int $commentId): void
    {
        $user = Auth::user();

        if (! $user) {
            return;
        }

        $comment = Comment::findOrFail($commentId);

        $exists = $comment->likedBy()->where('user_id', $user->id)->exists();

        if ($exists) {
            $comment->likedBy()->detach($user->id);
        } else {
            $comment->likedBy()->attach($user->id, ['created_at' => now()]);

            if ($comment->user_id !== $user->id) {
                $comment->user->notify(new EventCommentLiked($comment, $user));
            }
        }
    }

    public function deleteComment(int $commentId): void
    {
        $comment = Comment::findOrFail($commentId);

        Gate::authorize('delete', $comment);

        $comment->delete();
    }

    public function render(): View
    {
        $comments = $this->event->comments()
            ->whereNull('parent_id')
            ->with(['user', 'replies.user', 'replies.likedBy', 'likedBy'])
            ->latest()
            ->paginate(15);

        return view('livewire.comment-thread', [
            'comments' => $comments,
        ]);
    }

    private function notifyNewComment(Comment $comment): void
    {
        $hostIds = $this->event->hosts()->pluck('users.id')->toArray();

        $goingUserIds = $this->event->rsvps()
            ->where('status', RsvpStatus::Going)
            ->pluck('user_id')
            ->toArray();

        $recipientIds = collect(array_merge($hostIds, $goingUserIds))
            ->unique()
            ->reject(fn (int $id): bool => $id === $comment->user_id)
            ->values();

        $recipients = User::whereIn('id', $recipientIds)->get();

        foreach ($recipients as $recipient) {
            $recipient->notify(new NewEventComment($comment, $this->event));
        }
    }

    private function notifyReply(Comment $reply, Comment $parent): void
    {
        if ($parent->user_id !== $reply->user_id) {
            $parent->user->notify(new EventCommentReply($reply, $this->event));
        }
    }
}
