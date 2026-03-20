<?php

namespace App\Livewire;

use App\Events\DirectMessageSent;
use App\Models\Block;
use App\Models\Conversation;
use App\Models\ConversationParticipant;
use App\Models\DirectMessage;
use App\Notifications\NewDirectMessage;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Component;

class ConversationView extends Component
{
    public Conversation $conversation;

    public string $body = '';

    public ?string $cursor = null;

    public bool $hasMoreMessages = true;

    public function mount(Conversation $conversation): void
    {
        $participant = ConversationParticipant::where('conversation_id', $conversation->id)
            ->where('user_id', Auth::id())
            ->first();

        if (! $participant) {
            abort(403);
        }

        $this->conversation = $conversation;

        $participant->update(['last_read_at' => now()]);
    }

    public function sendMessage(): void
    {
        $this->validate([
            'body' => ['required', 'string', 'max:5000'],
        ]);

        $otherUserId = ConversationParticipant::where('conversation_id', $this->conversation->id)
            ->where('user_id', '!=', Auth::id())
            ->value('user_id');

        $isBlocked = Block::where(function ($query) use ($otherUserId) {
            $query->where('blocker_id', Auth::id())->where('blocked_id', $otherUserId);
        })->orWhere(function ($query) use ($otherUserId) {
            $query->where('blocker_id', $otherUserId)->where('blocked_id', Auth::id());
        })->exists();

        if ($isBlocked) {
            abort(403, 'You cannot message this user.');
        }

        $key = 'dm.'.Auth::id();

        if (RateLimiter::tooManyAttempts($key, 20)) {
            abort(429);
        }

        RateLimiter::hit($key, 60);

        $message = DirectMessage::create([
            'conversation_id' => $this->conversation->id,
            'user_id' => Auth::id(),
            'body' => $this->body,
        ]);

        $this->body = '';

        DirectMessageSent::dispatch($message);

        $otherParticipant = ConversationParticipant::where('conversation_id', $this->conversation->id)
            ->where('user_id', '!=', Auth::id())
            ->first();

        $notificationBlocked = Block::where(function ($query) use ($otherParticipant) {
            $query->where('blocker_id', Auth::id())->where('blocked_id', $otherParticipant?->user_id);
        })->orWhere(function ($query) use ($otherParticipant) {
            $query->where('blocker_id', $otherParticipant?->user_id)->where('blocked_id', Auth::id());
        })->exists();

        if ($otherParticipant && ! $otherParticipant->is_muted && ! $notificationBlocked) {
            $otherParticipant->user->notify(new NewDirectMessage($message));
        }

        ConversationParticipant::where('conversation_id', $this->conversation->id)
            ->where('user_id', Auth::id())
            ->update(['last_read_at' => now()]);
    }

    public function deleteMessage(int $messageId): void
    {
        $message = DirectMessage::where('conversation_id', $this->conversation->id)
            ->where('id', $messageId)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        $message->delete();
    }

    public function loadMore(): void
    {
        // Handled in render via cursor
    }

    public function render(): View
    {
        $query = $this->conversation->messages()
            ->with('user')
            ->latest();

        if ($this->cursor) {
            $query->where('created_at', '<', $this->cursor);
        }

        $messages = $query->cursorPaginate(30);

        $this->hasMoreMessages = $messages->hasMorePages();

        $otherParticipant = $this->conversation->participants()
            ->where('user_id', '!=', Auth::id())
            ->with('user')
            ->first();

        return view('livewire.conversation-view', [
            'messages' => $messages,
            'otherParticipant' => $otherParticipant,
        ]);
    }
}
