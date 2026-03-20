<?php

namespace App\Livewire;

use App\Events\EventChatMessageSent;
use App\Models\Event;
use App\Models\EventChatMessage;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Component;
use Livewire\WithPagination;

class EventChat extends Component
{
    use WithPagination;

    public Event $event;

    public string $body = '';

    public ?int $replyingTo = null;

    public ?int $editingId = null;

    public string $editBody = '';

    public function mount(Event $event): void
    {
        $this->event = $event;
    }

    public function sendMessage(): void
    {
        Gate::authorize('send', [EventChatMessage::class, $this->event]);

        $key = 'chat.'.$this->event->id.'.'.Auth::id();

        if (RateLimiter::tooManyAttempts($key, 10)) {
            abort(429);
        }

        $this->validate([
            'body' => ['required', 'string', 'max:5000'],
        ]);

        RateLimiter::hit($key, 15);

        $message = EventChatMessage::create([
            'event_id' => $this->event->id,
            'user_id' => Auth::id(),
            'body' => $this->body,
            'reply_to_id' => $this->replyingTo,
        ]);

        $this->body = '';
        $this->replyingTo = null;

        EventChatMessageSent::dispatch($message);
    }

    public function startReply(int $messageId): void
    {
        $this->replyingTo = $messageId;
        $this->editingId = null;
        $this->editBody = '';
    }

    public function cancelReply(): void
    {
        $this->replyingTo = null;
    }

    public function startEdit(int $messageId): void
    {
        $message = EventChatMessage::findOrFail($messageId);

        Gate::authorize('edit', $message);

        $this->editingId = $messageId;
        $this->editBody = $message->body;
        $this->replyingTo = null;
    }

    public function cancelEdit(): void
    {
        $this->editingId = null;
        $this->editBody = '';
    }

    public function saveEdit(): void
    {
        $message = EventChatMessage::findOrFail($this->editingId);

        Gate::authorize('edit', $message);

        $this->validate([
            'editBody' => ['required', 'string', 'max:5000'],
        ]);

        $message->update(['body' => $this->editBody]);

        $this->editingId = null;
        $this->editBody = '';
    }

    public function deleteMessage(int $messageId): void
    {
        $message = EventChatMessage::findOrFail($messageId);

        Gate::authorize('delete', $message);

        $message->delete();
    }

    public function render(): View
    {
        $messages = $this->event->chatMessages()
            ->with(['user', 'replyTo.user'])
            ->latest()
            ->paginate(25);

        return view('livewire.event-chat', [
            'messages' => $messages,
        ]);
    }
}
