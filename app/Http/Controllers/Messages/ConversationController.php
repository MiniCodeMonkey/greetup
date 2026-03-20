<?php

namespace App\Http\Controllers\Messages;

use App\Http\Controllers\Controller;
use App\Http\Requests\Messages\StartConversationRequest;
use App\Models\Block;
use App\Models\Conversation;
use App\Models\ConversationParticipant;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class ConversationController extends Controller
{
    public function index(): View
    {
        $user = request()->user();

        $conversations = Conversation::whereHas('participants', function ($query) use ($user) {
            $query->where('user_id', $user->id);
        })
            ->with(['participants.user', 'messages' => function ($query) {
                $query->latest()->limit(1);
            }])
            ->withMax('messages', 'created_at')
            ->orderByDesc('messages_max_created_at')
            ->paginate(20);

        $participantMap = ConversationParticipant::where('user_id', $user->id)
            ->whereIn('conversation_id', $conversations->pluck('id'))
            ->pluck('last_read_at', 'conversation_id');

        return view('messages.index', [
            'conversations' => $conversations,
            'participantMap' => $participantMap,
        ]);
    }

    public function store(StartConversationRequest $request): RedirectResponse
    {
        $senderId = $request->user()->id;
        $recipientId = (int) $request->validated('recipient_id');

        if ($senderId === $recipientId) {
            return back()->withErrors(['recipient_id' => 'You cannot start a conversation with yourself.']);
        }

        $isBlocked = Block::where(function ($query) use ($senderId, $recipientId) {
            $query->where('blocker_id', $senderId)->where('blocked_id', $recipientId);
        })->orWhere(function ($query) use ($senderId, $recipientId) {
            $query->where('blocker_id', $recipientId)->where('blocked_id', $senderId);
        })->exists();

        if ($isBlocked) {
            return back()->withErrors(['recipient_id' => 'You cannot message this user.']);
        }

        $existingConversation = Conversation::whereHas('participants', function ($query) use ($senderId) {
            $query->where('user_id', $senderId);
        })->whereHas('participants', function ($query) use ($recipientId) {
            $query->where('user_id', $recipientId);
        })->first();

        if ($existingConversation) {
            return redirect()->route('messages.show', $existingConversation);
        }

        $conversation = Conversation::create();

        ConversationParticipant::create([
            'conversation_id' => $conversation->id,
            'user_id' => $senderId,
        ]);

        ConversationParticipant::create([
            'conversation_id' => $conversation->id,
            'user_id' => $recipientId,
        ]);

        return redirect()->route('messages.show', $conversation);
    }
}
