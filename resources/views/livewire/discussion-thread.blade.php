<div data-testid="discussion-thread">
    {{-- Discussion body --}}
    <div class="mb-6 rounded-lg bg-neutral-50 p-4" data-testid="discussion-body">
        <div class="flex items-start gap-3">
            <x-avatar :user="$discussion->author" size="sm" />
            <div class="min-w-0 flex-1">
                <div class="flex items-center gap-2">
                    <span class="text-sm font-medium text-neutral-900">{{ $discussion->author->name }}</span>
                    <span class="text-xs text-neutral-400">{{ $discussion->created_at->diffForHumans() }}</span>
                </div>
                <div class="prose prose-sm mt-1 max-w-none text-neutral-700">
                    {!! $discussion->body_html !!}
                </div>
            </div>
        </div>
    </div>

    {{-- Replies --}}
    <h3 class="mb-4 text-sm font-medium text-neutral-900">Replies ({{ $replies->total() }})</h3>

    @forelse ($replies as $reply)
        <div class="mb-4 rounded-lg bg-neutral-50 p-4" data-testid="discussion-reply" wire:key="reply-{{ $reply->id }}">
            <div class="flex items-start gap-3">
                <x-avatar :user="$reply->user" size="sm" />
                <div class="min-w-0 flex-1">
                    <div class="flex items-center gap-2">
                        <span class="text-sm font-medium text-neutral-900">{{ $reply->user->name }}</span>
                        <span class="text-xs text-neutral-400">{{ $reply->created_at->diffForHumans() }}</span>
                    </div>
                    <div class="prose prose-sm mt-1 max-w-none text-neutral-700">
                        {!! $reply->body_html !!}
                    </div>
                    @auth
                        @if (auth()->id() === $reply->user_id || Gate::allows('deleteReply', $reply))
                            <div class="mt-2">
                                <button
                                    wire:click="deleteReply({{ $reply->id }})"
                                    wire:confirm="Are you sure you want to delete this reply?"
                                    class="text-xs text-neutral-400 hover:text-red-500"
                                    data-testid="delete-reply"
                                >
                                    Delete
                                </button>
                            </div>
                        @endif
                    @endauth
                </div>
            </div>
        </div>
    @empty
        <p class="text-sm text-neutral-500" data-testid="no-replies">No replies yet.</p>
    @endforelse

    {{-- Pagination --}}
    @if ($replies->hasPages())
        <div class="mt-4">
            {{ $replies->links() }}
        </div>
    @endif

    {{-- Reply form --}}
    @auth
        @if (! $discussion->is_locked)
            <form wire:submit="addReply" class="mt-6" data-testid="reply-form">
                <textarea
                    wire:model="body"
                    placeholder="Write a reply... (Markdown supported)"
                    class="w-full rounded-lg border border-neutral-200 px-3 py-2 text-sm text-neutral-700 placeholder-neutral-400 focus:border-green-500 focus:outline-none"
                    rows="3"
                ></textarea>
                @error('body')
                    <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                @enderror
                <div class="mt-2 flex justify-end">
                    <button type="submit" class="rounded-md bg-green-500 px-4 py-2 text-sm font-medium text-white hover:bg-green-700">
                        Reply
                    </button>
                </div>
            </form>
        @else
            <p class="mt-6 text-sm text-neutral-500" data-testid="locked-notice">This discussion is locked. No new replies can be added.</p>
        @endif
    @endauth
</div>
