<div data-testid="event-feedback">
    {{-- Aggregate rating --}}
    @if ($feedbackCount > 0)
        <div class="mb-6 rounded-lg bg-neutral-50 p-4" data-testid="feedback-aggregate">
            <div class="flex items-center gap-3">
                <div class="flex items-center gap-1">
                    @for ($i = 1; $i <= 5; $i++)
                        <svg class="h-5 w-5 {{ $i <= round($averageRating) ? 'text-gold-500' : 'text-neutral-200' }}" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                        </svg>
                    @endfor
                </div>
                <span class="text-sm font-medium text-neutral-900">{{ $averageRating }}</span>
                <span class="text-sm text-neutral-500">({{ $feedbackCount }} {{ Str::plural('review', $feedbackCount) }})</span>
            </div>
        </div>
    @else
        <p class="mb-6 text-sm text-neutral-500">No feedback yet.</p>
    @endif

    {{-- User's existing feedback --}}
    @if ($userFeedback)
        <div class="mb-6 rounded-lg border border-green-200 bg-green-50 p-4" data-testid="user-feedback">
            <p class="text-sm font-medium text-green-700">You already submitted feedback for this event.</p>
            <div class="mt-2 flex items-center gap-1">
                @for ($i = 1; $i <= 5; $i++)
                    <svg class="h-4 w-4 {{ $i <= $userFeedback->rating ? 'text-gold-500' : 'text-neutral-200' }}" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                    </svg>
                @endfor
            </div>
            @if ($userFeedback->body)
                <p class="mt-2 text-sm text-neutral-700">{{ $userFeedback->body }}</p>
            @endif
        </div>
    @endif

    {{-- Feedback form --}}
    @if ($canSubmit)
        <div class="mb-6 rounded-lg border border-neutral-200 p-4" data-testid="feedback-form">
            <h3 class="text-sm font-medium text-neutral-900">Leave your feedback</h3>
            <form wire:submit="submitFeedback" class="mt-3 space-y-4">
                <div>
                    <label class="text-sm text-neutral-700">Rating</label>
                    <div class="mt-1 flex items-center gap-1">
                        @for ($i = 1; $i <= 5; $i++)
                            <button type="button" wire:click="$set('rating', {{ $i }})" class="focus:outline-none">
                                <svg class="h-6 w-6 {{ $i <= $rating ? 'text-gold-500' : 'text-neutral-200' }} cursor-pointer hover:text-gold-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                </svg>
                            </button>
                        @endfor
                    </div>
                    @error('rating')
                        <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="feedback-body" class="text-sm text-neutral-700">Written feedback (optional)</label>
                    <textarea id="feedback-body" wire:model="body" rows="3" class="mt-1 w-full rounded-md border border-neutral-200 px-3 py-2 text-sm text-neutral-700 focus:border-green-500 focus:outline-none" placeholder="Share your experience..."></textarea>
                    @error('body')
                        <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>
                <button type="submit" class="rounded-md bg-green-500 px-4 py-2 text-sm font-medium text-white hover:bg-green-700">Submit Feedback</button>
            </form>
        </div>
    @endif

    {{-- Attributed feedback list (organizer+ only) --}}
    @if ($canViewAttribution && $feedbackItems->isNotEmpty())
        <div data-testid="feedback-list-attributed">
            <h3 class="mb-3 text-sm font-medium text-neutral-900">All Feedback</h3>
            <div class="space-y-4">
                @foreach ($feedbackItems as $item)
                    <div class="rounded-lg border border-neutral-200 p-4" data-testid="feedback-item">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <x-avatar :user="$item->user" size="sm" />
                                <span class="text-sm font-medium text-neutral-700" data-testid="feedback-author">{{ $item->user->name }}</span>
                            </div>
                            <div class="flex items-center gap-1">
                                @for ($i = 1; $i <= 5; $i++)
                                    <svg class="h-4 w-4 {{ $i <= $item->rating ? 'text-gold-500' : 'text-neutral-200' }}" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                    </svg>
                                @endfor
                            </div>
                        </div>
                        @if ($item->body)
                            <p class="mt-2 text-sm text-neutral-700">{{ $item->body }}</p>
                        @endif
                        <p class="mt-1 text-xs text-neutral-400">{{ $item->created_at->diffForHumans() }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Anonymous feedback text for non-organizers --}}
    @if (! $canViewAttribution && $feedbackCount > 0)
        @php
            $feedbackWithBody = $this->event->feedback()->whereNotNull('body')->get();
        @endphp
        @if ($feedbackWithBody->isNotEmpty())
            <div data-testid="feedback-list-anonymous">
                <h3 class="mb-3 text-sm font-medium text-neutral-900">Feedback</h3>
                <div class="space-y-3">
                    @foreach ($feedbackWithBody as $item)
                        <div class="rounded-lg border border-neutral-200 p-4" data-testid="feedback-item-anonymous">
                            <div class="flex items-center gap-1">
                                @for ($i = 1; $i <= 5; $i++)
                                    <svg class="h-4 w-4 {{ $i <= $item->rating ? 'text-gold-500' : 'text-neutral-200' }}" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                    </svg>
                                @endfor
                            </div>
                            <p class="mt-2 text-sm text-neutral-700">{{ $item->body }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    @endif
</div>
