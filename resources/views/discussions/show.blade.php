<x-layouts.app :title="$discussion->title">
    <div class="mx-auto max-w-3xl px-4 py-8">
        <div class="mb-6">
            <a href="{{ route('groups.show', ['group' => $group->slug, 'tab' => 'discussions']) }}" class="text-sm text-green-500 hover:text-green-700">&larr; Back to discussions</a>
        </div>

        <h1 class="text-2xl font-bold text-neutral-900" data-testid="discussion-title">{{ $discussion->title }}</h1>

        @if ($discussion->is_pinned)
            <span class="mt-2 inline-flex items-center rounded-full bg-green-50 px-2 py-0.5 text-xs font-medium text-green-700">Pinned</span>
        @endif

        @if ($discussion->is_locked)
            <span class="mt-2 inline-flex items-center rounded-full bg-neutral-100 px-2 py-0.5 text-xs font-medium text-neutral-600">Locked</span>
        @endif

        <div class="mt-6">
            <livewire:discussion-thread :discussion="$discussion" />
        </div>
    </div>
</x-layouts.app>
