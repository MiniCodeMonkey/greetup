<div data-testid="event-chat">
    {{-- Send message form --}}
    @auth
        @can('send', [\App\Models\EventChatMessage::class, $event])
            <form wire:submit="sendMessage" class="mb-6" data-testid="chat-form">
                @if ($replyingTo)
                    @php
                        $replyMessage = $messages->firstWhere('id', $replyingTo);
                    @endphp
                    @if ($replyMessage)
                        <div class="mb-2 flex items-center gap-2 rounded-md bg-neutral-100 px-3 py-2 text-xs text-neutral-500">
                            <span>Replying to <strong>{{ $replyMessage->user->name }}</strong></span>
                            <button type="button" wire:click="cancelReply" class="ml-auto text-neutral-400 hover:text-neutral-700">&times;</button>
                        </div>
                    @endif
                @endif
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
        @endcan
    @endauth

    {{-- Messages list --}}
    @forelse ($messages as $chatMessage)
        <div class="mb-3 rounded-lg bg-neutral-50 p-4" data-testid="chat-message" wire:key="chat-{{ $chatMessage->id }}">
            {{-- Reply context --}}
            @if ($chatMessage->replyTo)
                <div class="mb-2 border-l-2 border-neutral-300 pl-3 text-xs text-neutral-500" data-testid="reply-context">
                    <span class="font-medium">{{ $chatMessage->replyTo->user->name }}</span>: {{ Str::limit($chatMessage->replyTo->body, 80) }}
                </div>
            @endif

            <div class="flex items-start gap-3">
                <x-avatar :user="$chatMessage->user" size="sm" />
                <div class="min-w-0 flex-1">
                    <div class="flex items-center gap-2">
                        <span class="text-sm font-medium text-neutral-900">{{ $chatMessage->user->name }}</span>
                        <span class="text-xs text-neutral-400">{{ $chatMessage->created_at->diffForHumans() }}</span>
                    </div>

                    @if ($editingId === $chatMessage->id)
                        <form wire:submit="saveEdit" class="mt-1" data-testid="edit-form">
                            <textarea
                                wire:model="editBody"
                                class="w-full rounded-lg border border-neutral-200 px-3 py-2 text-sm text-neutral-700 focus:border-green-500 focus:outline-none"
                                rows="2"
                            ></textarea>
                            @error('editBody')
                                <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                            @enderror
                            <div class="mt-2 flex gap-2">
                                <button type="submit" class="rounded-md bg-green-500 px-3 py-1.5 text-xs font-medium text-white hover:bg-green-700">Save</button>
                                <button type="button" wire:click="cancelEdit" class="rounded-md px-3 py-1.5 text-xs font-medium text-neutral-500 hover:text-neutral-700">Cancel</button>
                            </div>
                        </form>
                    @else
                        <p class="mt-1 text-sm text-neutral-700">{{ $chatMessage->body }}</p>
                    @endif

                    <div class="mt-2 flex items-center gap-3">
                        @auth
                            @can('send', [\App\Models\EventChatMessage::class, $event])
                                <button
                                    wire:click="startReply({{ $chatMessage->id }})"
                                    class="text-xs text-neutral-400 hover:text-neutral-700"
                                >
                                    Reply
                                </button>
                            @endcan
                            @can('edit', $chatMessage)
                                <button
                                    wire:click="startEdit({{ $chatMessage->id }})"
                                    class="text-xs text-neutral-400 hover:text-neutral-700"
                                    data-testid="edit-message"
                                >
                                    Edit
                                </button>
                            @endcan
                            @can('delete', $chatMessage)
                                <button
                                    wire:click="deleteMessage({{ $chatMessage->id }})"
                                    wire:confirm="Are you sure you want to delete this message?"
                                    class="text-xs text-neutral-400 hover:text-red-500"
                                    data-testid="delete-message"
                                >
                                    Delete
                                </button>
                            @endcan
                        @endauth
                    </div>
                </div>
            </div>
        </div>
    @empty
        <p class="text-sm text-neutral-500" data-testid="no-messages">No messages yet. Start the conversation!</p>
    @endforelse

    {{-- Pagination --}}
    @if ($messages->hasPages())
        <div class="mt-4">
            {{ $messages->links() }}
        </div>
    @endif
</div>
