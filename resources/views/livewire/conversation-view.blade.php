<div data-testid="conversation-view">
    {{-- Header --}}
    <div class="mb-6 flex items-center gap-3 border-b border-neutral-200 pb-4">
        <a href="{{ route('messages.index') }}" class="text-neutral-400 hover:text-neutral-700">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M9.707 16.707a1 1 0 01-1.414 0l-6-6a1 1 0 010-1.414l6-6a1 1 0 011.414 1.414L5.414 9H17a1 1 0 110 2H5.414l4.293 4.293a1 1 0 010 1.414z" clip-rule="evenodd" />
            </svg>
        </a>
        @if ($otherParticipant)
            <x-avatar :user="$otherParticipant->user" size="md" />
            <span class="text-lg font-medium text-neutral-900">{{ $otherParticipant->user->name }}</span>
        @else
            <span class="text-lg font-medium text-neutral-500">Deleted User</span>
        @endif
    </div>

    {{-- Load more --}}
    @if ($messages->hasMorePages())
        <div class="mb-4 text-center">
            <button
                wire:click="$set('cursor', '{{ $messages->last()?->created_at?->toIso8601String() }}')"
                class="text-sm text-green-500 hover:text-green-700"
                data-testid="load-more"
            >
                Load older messages
            </button>
        </div>
    @endif

    {{-- Messages --}}
    <div class="space-y-3" data-testid="message-list">
        @forelse ($messages->reverse() as $message)
            <div
                class="flex items-start gap-3 rounded-lg p-3 {{ $message->user_id === auth()->id() ? 'bg-green-50' : 'bg-neutral-50' }}"
                data-testid="dm-message"
                wire:key="dm-{{ $message->id }}"
            >
                <x-avatar :user="$message->user" size="sm" />
                <div class="min-w-0 flex-1">
                    <div class="flex items-center gap-2">
                        <span class="text-sm font-medium text-neutral-900">{{ $message->user->name }}</span>
                        <span class="text-xs text-neutral-400">{{ $message->created_at->diffForHumans() }}</span>
                    </div>
                    <p class="mt-1 text-sm text-neutral-700">{{ $message->body }}</p>

                    @if ($message->user_id === auth()->id())
                        <div class="mt-2">
                            <button
                                wire:click="deleteMessage({{ $message->id }})"
                                wire:confirm="Are you sure you want to delete this message?"
                                class="text-xs text-neutral-400 hover:text-red-500"
                                data-testid="delete-dm"
                            >
                                Delete
                            </button>
                        </div>
                    @endif
                </div>
            </div>
        @empty
            <p class="text-sm text-neutral-500" data-testid="no-messages">No messages yet. Start the conversation!</p>
        @endforelse
    </div>

    {{-- Send message form --}}
    <form wire:submit="sendMessage" class="mt-6 border-t border-neutral-200 pt-4" data-testid="dm-form">
        <textarea
            wire:model="body"
            placeholder="Type a message..."
            class="w-full rounded-lg border border-neutral-200 px-3 py-2 text-sm text-neutral-700 placeholder-neutral-400 focus:border-green-500 focus:outline-none"
            rows="2"
        ></textarea>
        @error('body')
            <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
        @enderror
        <div class="mt-2 flex justify-end">
            <button type="submit" class="rounded-md bg-green-500 px-4 py-2 text-sm font-medium text-white hover:bg-green-700">
                Send
            </button>
        </div>
    </form>
</div>
