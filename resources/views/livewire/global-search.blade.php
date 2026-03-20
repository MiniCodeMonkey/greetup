<div class="mx-auto max-w-6xl px-4 py-8 sm:px-6">
    {{-- Header --}}
    <div class="mb-8">
        <h1 class="font-display text-3xl font-medium text-neutral-900">Search</h1>

        {{-- Search bar --}}
        <div class="mt-4">
            <div class="relative">
                <svg class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-neutral-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                </svg>
                <input
                    type="text"
                    wire:model.live.debounce.300ms="query"
                    placeholder="Search groups, events, members..."
                    class="w-full rounded-lg border-neutral-200 py-2.5 pl-10 pr-4 text-sm text-neutral-900 placeholder-neutral-400 focus:border-green-500 focus:ring-green-500"
                    style="border: 0.5px solid var(--color-neutral-200)"
                    autofocus
                >
            </div>
        </div>
    </div>

    @if ($query !== '')
        {{-- Groups section --}}
        @if ($groups->isNotEmpty())
            <div class="mb-8">
                <h2 class="mb-4 font-display text-lg font-medium text-neutral-900">Groups</h2>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($groups as $group)
                        <a
                            href="{{ route('groups.show', $group) }}"
                            class="block overflow-hidden rounded-xl transition-shadow hover:shadow-md"
                            style="border: 0.5px solid var(--color-neutral-200)"
                            wire:key="group-{{ $group->id }}"
                        >
                            <div class="p-4">
                                <h3 class="font-medium text-neutral-900" style="font-size: 15px;">{{ $group->name }}</h3>
                                @if ($group->location)
                                    <p class="mt-0.5 text-neutral-500" style="font-size: 13px;">{{ $group->location }}</p>
                                @endif
                                <p class="mt-1 text-neutral-500" style="font-size: 13px;">{{ $group->members_count }} {{ Str::plural('member', $group->members_count) }}</p>
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Events section --}}
        @if ($events->isNotEmpty())
            <div class="mb-8">
                <h2 class="mb-4 font-display text-lg font-medium text-neutral-900">Events</h2>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($events as $event)
                        <a
                            href="{{ route('events.show', [$event->group, $event]) }}"
                            class="block overflow-hidden rounded-xl transition-shadow hover:shadow-md"
                            style="border: 0.5px solid var(--color-neutral-200)"
                            wire:key="event-{{ $event->id }}"
                        >
                            <div class="p-4">
                                <h3 class="font-medium text-neutral-900" style="font-size: 15px;">{{ $event->name }}</h3>
                                @if ($event->group)
                                    <p class="mt-0.5 text-neutral-500" style="font-size: 13px;">{{ $event->group->name }}</p>
                                @endif
                                @if ($event->starts_at)
                                    <p class="mt-1 text-neutral-500" style="font-size: 13px;">{{ $event->starts_at->format('M j, Y \a\t g:i A') }}</p>
                                @endif
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Members section --}}
        @if ($users->isNotEmpty())
            <div class="mb-8">
                <h2 class="mb-4 font-display text-lg font-medium text-neutral-900">Members</h2>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($users as $member)
                        <a
                            href="{{ route('members.show', $member) }}"
                            class="flex items-center gap-3 rounded-xl p-4 transition-shadow hover:shadow-md"
                            style="border: 0.5px solid var(--color-neutral-200)"
                            wire:key="user-{{ $member->id }}"
                        >
                            <x-avatar :user="$member" size="md" />
                            <div>
                                <h3 class="font-medium text-neutral-900" style="font-size: 15px;">{{ $member->name }}</h3>
                                @if ($member->bio)
                                    <p class="mt-0.5 line-clamp-1 text-neutral-500" style="font-size: 13px;">{{ $member->bio }}</p>
                                @endif
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Empty state --}}
        @if ($groups->isEmpty() && $events->isEmpty() && $users->isEmpty())
            <div class="py-16 text-center">
                <p class="text-lg font-medium text-neutral-700">No results found</p>
                <p class="mt-2 text-sm text-neutral-500">Try adjusting your search terms.</p>
            </div>
        @endif
    @else
        <div class="py-16 text-center">
            <p class="text-lg font-medium text-neutral-700">Search for groups, events, and members</p>
            <p class="mt-2 text-sm text-neutral-500">Enter a search term above to get started.</p>
        </div>
    @endif
</div>
