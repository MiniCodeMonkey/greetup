<div class="mx-auto max-w-6xl px-4 py-8 sm:px-6">
    {{-- Header --}}
    <div class="mb-8">
        <h1 class="font-display text-3xl font-medium text-neutral-900">Browse Groups</h1>

        {{-- Search bar --}}
        <div class="mt-4 flex flex-col gap-4 sm:flex-row sm:items-center">
            <div class="relative flex-1">
                <svg class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-neutral-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                </svg>
                <input
                    type="text"
                    wire:model.live.debounce.300ms="search"
                    placeholder="Search groups..."
                    class="w-full rounded-lg border-neutral-200 py-2.5 pl-10 pr-4 text-sm text-neutral-900 placeholder-neutral-400 focus:border-green-500 focus:ring-green-500"
                    style="border: 0.5px solid var(--color-neutral-200)"
                >
            </div>
        </div>

        {{-- Filter controls --}}
        <div class="mt-4 flex flex-wrap gap-2">
            {{-- Topic filter --}}
            <select
                wire:model.live="topic"
                class="rounded-pill border-neutral-200 bg-white px-4 py-1.5 text-sm text-neutral-700 focus:border-green-500 focus:ring-green-500"
                style="border: 0.5px solid var(--color-neutral-200)"
            >
                <option value="">All topics</option>
                @foreach ($topics as $topicName)
                    <option value="{{ $topicName }}">{{ $topicName }}</option>
                @endforeach
            </select>

            {{-- Distance filter --}}
            @if ($latitude && $longitude)
                <select
                    wire:model.live="distance"
                    class="rounded-pill border-neutral-200 bg-white px-4 py-1.5 text-sm text-neutral-700 focus:border-green-500 focus:ring-green-500"
                    style="border: 0.5px solid var(--color-neutral-200)"
                >
                    <option value="10">10 km</option>
                    <option value="25">25 km</option>
                    <option value="50">50 km</option>
                    <option value="100">100 km</option>
                    <option value="250">Any distance</option>
                </select>
            @endif

            {{-- Sort --}}
            <select
                wire:model.live="sort"
                class="rounded-pill border-neutral-200 bg-white px-4 py-1.5 text-sm text-neutral-700 focus:border-green-500 focus:ring-green-500"
                style="border: 0.5px solid var(--color-neutral-200)"
            >
                <option value="">{{ $search ? 'Relevance' : 'Newest' }}</option>
                @if ($search)
                    <option value="newest">Newest</option>
                @endif
                <option value="most_members">Most members</option>
                <option value="most_active">Most active</option>
            </select>
        </div>
    </div>

    {{-- Groups grid --}}
    @if ($groups->isNotEmpty())
        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($groups as $group)
                <a
                    href="{{ route('groups.show', $group) }}"
                    class="block overflow-hidden rounded-xl transition-shadow hover:shadow-md"
                    style="border: 0.5px solid var(--color-neutral-200)"
                    wire:key="group-{{ $group->id }}"
                >
                    <div class="relative overflow-hidden bg-green-900" style="min-height: 80px;">
                        <x-blob color="#15803d" size="140" opacity="0.15" class="-right-4 -top-4" />
                    </div>
                    <div class="p-4">
                        <h3 class="font-medium text-neutral-900" style="font-size: 15px;">{{ $group->name }}</h3>
                        @if ($group->location)
                            <p class="mt-0.5 text-neutral-500" style="font-size: 13px;">{{ $group->location }}</p>
                        @endif
                        <div class="mt-3 flex items-center gap-3">
                            <span class="text-neutral-500" style="font-size: 13px;">{{ $group->members_count }} {{ Str::plural('member', $group->members_count) }}</span>
                            <span class="text-neutral-300">·</span>
                            <span class="text-neutral-500" style="font-size: 13px;">{{ $group->events_count }} {{ Str::plural('event', $group->events_count) }}</span>
                        </div>
                    </div>
                </a>
            @endforeach
        </div>
    @endif

    {{-- Infinite scroll trigger --}}
    @if ($hasMorePages)
        <div wire:intersect="loadMore" class="flex justify-center py-8">
            <div wire:loading wire:target="loadMore" class="text-sm text-neutral-500">Loading more groups...</div>
        </div>
    @endif

    {{-- Empty state --}}
    @if ($groups->isEmpty())
        <div class="py-16 text-center">
            <p class="text-lg font-medium text-neutral-700">No groups found</p>
            <p class="mt-2 text-sm text-neutral-500">Try adjusting your filters or search terms.</p>
        </div>
    @endif
</div>
