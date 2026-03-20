<div data-testid="comment-thread">
    {{-- New comment form --}}
    @auth
        <form wire:submit="addComment" class="mb-6" data-testid="comment-form">
            <textarea
                wire:model="body"
                placeholder="Write a comment... (Markdown supported)"
                class="w-full rounded-lg border border-neutral-200 px-3 py-2 text-sm text-neutral-700 placeholder-neutral-400 focus:border-green-500 focus:outline-none"
                rows="3"
            ></textarea>
            @error('body')
                <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
            @enderror
            <div class="mt-2 flex justify-end">
                <button type="submit" class="rounded-md bg-green-500 px-4 py-2 text-sm font-medium text-white hover:bg-green-700">
                    Comment
                </button>
            </div>
        </form>
    @endauth

    {{-- Comments list --}}
    @forelse ($comments as $comment)
        <div class="mb-4 rounded-lg bg-neutral-50 p-4" data-testid="comment" wire:key="comment-{{ $comment->id }}">
            <div class="flex items-start gap-3">
                <x-avatar :user="$comment->user" size="sm" />
                <div class="min-w-0 flex-1">
                    <div class="flex items-center gap-2">
                        <span class="text-sm font-medium text-neutral-900">{{ $comment->user->name }}</span>
                        <span class="text-xs text-neutral-400">{{ $comment->created_at->diffForHumans() }}</span>
                    </div>
                    <div class="prose prose-sm mt-1 max-w-none text-neutral-700">
                        {!! $comment->body_html !!}
                    </div>
                    <div class="mt-2 flex items-center gap-3">
                        @auth
                            <button
                                wire:click="toggleLike({{ $comment->id }})"
                                class="text-xs {{ $comment->likedBy->contains('id', auth()->id()) ? 'text-coral-500 font-medium' : 'text-neutral-400 hover:text-neutral-700' }}"
                                data-testid="like-button"
                            >
                                {{ $comment->likedBy->count() > 0 ? $comment->likedBy->count() . ' ' : '' }}{{ $comment->likedBy->contains('id', auth()->id()) ? 'Liked' : 'Like' }}
                            </button>
                            <button
                                wire:click="startReply({{ $comment->id }})"
                                class="text-xs text-neutral-400 hover:text-neutral-700"
                            >
                                Reply
                            </button>
                            @if (auth()->id() === $comment->user_id || Gate::allows('delete', $comment))
                                <button
                                    wire:click="deleteComment({{ $comment->id }})"
                                    wire:confirm="Are you sure you want to delete this comment?"
                                    class="text-xs text-neutral-400 hover:text-red-500"
                                    data-testid="delete-comment"
                                >
                                    Delete
                                </button>
                            @endif
                        @endauth
                    </div>

                    {{-- Reply form --}}
                    @if ($replyingTo === $comment->id)
                        <form wire:submit="addReply" class="mt-3" data-testid="reply-form">
                            <textarea
                                wire:model="replyBody"
                                placeholder="Write a reply..."
                                class="w-full rounded-lg border border-neutral-200 px-3 py-2 text-sm text-neutral-700 placeholder-neutral-400 focus:border-green-500 focus:outline-none"
                                rows="2"
                            ></textarea>
                            @error('replyBody')
                                <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                            @enderror
                            <div class="mt-2 flex gap-2">
                                <button type="submit" class="rounded-md bg-green-500 px-3 py-1.5 text-xs font-medium text-white hover:bg-green-700">
                                    Reply
                                </button>
                                <button type="button" wire:click="cancelReply" class="rounded-md px-3 py-1.5 text-xs font-medium text-neutral-500 hover:text-neutral-700">
                                    Cancel
                                </button>
                            </div>
                        </form>
                    @endif

                    {{-- Replies --}}
                    @if ($comment->replies->isNotEmpty())
                        <div class="mt-3 space-y-3 border-l-2 border-neutral-200 pl-4">
                            @foreach ($comment->replies as $reply)
                                <div data-testid="comment-reply" wire:key="reply-{{ $reply->id }}">
                                    <div class="flex items-start gap-2">
                                        <x-avatar :user="$reply->user" size="sm" />
                                        <div class="min-w-0 flex-1">
                                            <div class="flex items-center gap-2">
                                                <span class="text-sm font-medium text-neutral-900">{{ $reply->user->name }}</span>
                                                <span class="text-xs text-neutral-400">{{ $reply->created_at->diffForHumans() }}</span>
                                            </div>
                                            <div class="prose prose-sm mt-1 max-w-none text-neutral-700">
                                                {!! $reply->body_html !!}
                                            </div>
                                            <div class="mt-1 flex items-center gap-3">
                                                @auth
                                                    <button
                                                        wire:click="toggleLike({{ $reply->id }})"
                                                        class="text-xs {{ $reply->likedBy->contains('id', auth()->id()) ? 'text-coral-500 font-medium' : 'text-neutral-400 hover:text-neutral-700' }}"
                                                        data-testid="like-button"
                                                    >
                                                        {{ $reply->likedBy->count() > 0 ? $reply->likedBy->count() . ' ' : '' }}{{ $reply->likedBy->contains('id', auth()->id()) ? 'Liked' : 'Like' }}
                                                    </button>
                                                    @if (auth()->id() === $reply->user_id || Gate::allows('delete', $reply))
                                                        <button
                                                            wire:click="deleteComment({{ $reply->id }})"
                                                            wire:confirm="Are you sure you want to delete this reply?"
                                                            class="text-xs text-neutral-400 hover:text-red-500"
                                                            data-testid="delete-comment"
                                                        >
                                                            Delete
                                                        </button>
                                                    @endif
                                                @endauth
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @empty
        <p class="text-sm text-neutral-500" data-testid="no-comments">No comments yet.</p>
    @endforelse

    {{-- Pagination --}}
    @if ($comments->hasPages())
        <div class="mt-4">
            {{ $comments->links() }}
        </div>
    @endif
</div>
