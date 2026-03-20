<x-layouts.app title="Messages">
    <div class="mx-auto max-w-3xl px-4 py-10">
        <h1 class="text-2xl font-medium text-neutral-900">Messages</h1>

        @if ($conversations->isEmpty())
            <x-empty-state title="No conversations yet" description="Start a conversation by visiting a member's profile." />
        @else
            <div class="mt-6 divide-y divide-neutral-100 rounded-xl border border-neutral-200 bg-white">
                @foreach ($conversations as $conversation)
                    @php
                        $otherParticipant = $conversation->participants->first(fn ($p) => $p->user_id !== auth()->id());
                        $latestMessage = $conversation->messages->first();
                        $lastReadAt = $participantMap[$conversation->id] ?? null;
                        $isUnread = $latestMessage && (! $lastReadAt || $latestMessage->created_at->gt($lastReadAt));
                    @endphp
                    <a
                        href="{{ route('messages.show', $conversation) }}"
                        class="flex items-center gap-3 px-4 py-3 transition hover:bg-neutral-50"
                        data-testid="conversation-item"
                    >
                        @if ($otherParticipant)
                            <x-avatar :user="$otherParticipant->user" size="md" />
                        @endif
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center justify-between">
                                <span class="text-sm font-medium text-neutral-900 {{ $isUnread ? 'font-semibold' : '' }}">
                                    {{ $otherParticipant?->user?->name ?? 'Deleted User' }}
                                </span>
                                @if ($latestMessage)
                                    <span class="text-xs text-neutral-400">{{ $latestMessage->created_at->diffForHumans() }}</span>
                                @endif
                            </div>
                            @if ($latestMessage)
                                <p class="truncate text-sm {{ $isUnread ? 'font-medium text-neutral-700' : 'text-neutral-500' }}">
                                    {{ Str::limit($latestMessage->body, 80) }}
                                </p>
                            @else
                                <p class="text-sm text-neutral-400">No messages yet</p>
                            @endif
                        </div>
                        @if ($isUnread)
                            <span class="h-2.5 w-2.5 shrink-0 rounded-full bg-green-500" data-testid="unread-indicator"></span>
                        @endif
                    </a>
                @endforeach
            </div>

            @if ($conversations->hasPages())
                <div class="mt-6">
                    {{ $conversations->links() }}
                </div>
            @endif
        @endif
    </div>
</x-layouts.app>
