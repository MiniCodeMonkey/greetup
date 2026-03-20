<div class="mx-auto max-w-6xl px-4 py-8 sm:px-6">
    {{-- Header --}}
    <div class="mb-8">
        <h1 class="font-display text-3xl font-medium text-neutral-900">
            Events near
            <span class="text-green-500">{{ $locationName ?: 'you' }}</span>
        </h1>

        {{-- Search bar --}}
        <div class="mt-4 flex flex-col gap-4 sm:flex-row sm:items-center">
            <div class="relative flex-1">
                <svg class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-neutral-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                </svg>
                <input
                    type="text"
                    wire:model.live.debounce.300ms="search"
                    placeholder="Search events..."
                    class="w-full rounded-lg border-neutral-200 py-2.5 pl-10 pr-4 text-sm text-neutral-900 placeholder-neutral-400 focus:border-green-500 focus:ring-green-500"
                    style="border: 0.5px solid var(--color-neutral-200)"
                >
            </div>
        </div>

        {{-- Filter chips --}}
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

            {{-- Date range filter --}}
            <select
                wire:model.live="dateRange"
                class="rounded-pill border-neutral-200 bg-white px-4 py-1.5 text-sm text-neutral-700 focus:border-green-500 focus:ring-green-500"
                style="border: 0.5px solid var(--color-neutral-200)"
            >
                <option value="">Any date</option>
                <option value="today">Today</option>
                <option value="tomorrow">Tomorrow</option>
                <option value="this_week">This week</option>
                <option value="this_month">This month</option>
            </select>

            {{-- Event type filter --}}
            <select
                wire:model.live="eventType"
                class="rounded-pill border-neutral-200 bg-white px-4 py-1.5 text-sm text-neutral-700 focus:border-green-500 focus:ring-green-500"
                style="border: 0.5px solid var(--color-neutral-200)"
            >
                <option value="">All types</option>
                <option value="in_person">In person</option>
                <option value="online">Online</option>
                <option value="hybrid">Hybrid</option>
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
                    <option value="250">250 km</option>
                </select>
            @endif
        </div>
    </div>

    {{-- Location prompt for authenticated users without location --}}
    @if ($showLocationPrompt)
        <div class="mb-8 rounded-lg bg-gold-50 p-4 text-sm text-gold-900" style="border: 0.5px solid var(--color-gold-200)">
            <p class="font-medium">Set your location to see nearby events</p>
            <p class="mt-1 text-gold-700">
                <a href="{{ route('settings') }}" class="font-medium underline hover:text-gold-900">Update your profile</a>
                to get personalized event recommendations near you.
            </p>
        </div>
    @endif

    {{-- Featured events (first 2 in 2-col grid) --}}
    @if ($events->isNotEmpty())
        <div class="mb-8 grid grid-cols-1 gap-6 sm:grid-cols-2">
            @foreach ($events->take(2) as $event)
                <a
                    href="{{ route('events.show', [$event->group, $event]) }}"
                    class="block overflow-hidden rounded-xl"
                    style="border: 0.5px solid var(--color-neutral-200)"
                    wire:key="featured-{{ $event->id }}"
                >
                    @php
                        $typeValue = $event->event_type instanceof \App\Enums\EventType ? $event->event_type->value : $event->event_type;
                        $headerColors = ['hybrid' => 'bg-green-900', 'in_person' => 'bg-coral-900', 'online' => 'bg-violet-900'];
                        $accentColors = ['hybrid' => 'text-green-500', 'in_person' => 'text-coral-500', 'online' => 'text-violet-500'];
                        $blobColors = ['hybrid' => '#15803d', 'in_person' => '#FF6B4A', 'online' => '#7C3AED'];
                    @endphp
                    <div class="relative {{ $headerColors[$typeValue] ?? 'bg-coral-900' }} overflow-hidden" style="min-height: 96px;">
                        <x-blob :color="$blobColors[$typeValue] ?? '#FF6B4A'" size="160" opacity="0.15" class="-right-4 -top-4" />
                        <span class="absolute bottom-2 left-2 inline-flex items-center rounded-sm px-2 py-0.5 text-xs font-medium text-white" style="background: rgba(255,255,255,0.15);">
                            {{ $typeValue === 'in_person' ? 'In person' : ($typeValue === 'online' ? 'Online' : 'Hybrid') }}
                        </span>
                    </div>
                    <div class="p-4">
                        <p class="{{ $accentColors[$typeValue] ?? 'text-coral-500' }} font-medium uppercase" style="font-size: 11px; letter-spacing: 0.05em;">
                            {{ $event->starts_at->setTimezone($displayTimezone)->format('D, M j · g:ia') }}
                        </p>
                        <h3 class="mt-1 font-medium text-neutral-900" style="font-size: 15px;">{{ $event->name }}</h3>
                        <p class="mt-0.5 text-neutral-500" style="font-size: 13px;">{{ $event->group->name }}</p>
                        <div class="mt-3 flex items-center gap-1.5">
                            <span class="text-neutral-500" style="font-size: 13px;">{{ $event->rsvps_count ?? $event->rsvps->count() }} going</span>
                        </div>
                    </div>
                </a>
            @endforeach
        </div>
    @endif

    {{-- Remaining events (3-col grid) --}}
    @if ($events->count() > 2)
        <div class="mb-8 grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($events->skip(2) as $event)
                <a
                    href="{{ route('events.show', [$event->group, $event]) }}"
                    class="block overflow-hidden rounded-xl"
                    style="border: 0.5px solid var(--color-neutral-200)"
                    wire:key="event-{{ $event->id }}"
                >
                    @php
                        $typeValue = $event->event_type instanceof \App\Enums\EventType ? $event->event_type->value : $event->event_type;
                        $headerColors = ['hybrid' => 'bg-green-900', 'in_person' => 'bg-coral-900', 'online' => 'bg-violet-900'];
                        $accentColors = ['hybrid' => 'text-green-500', 'in_person' => 'text-coral-500', 'online' => 'text-violet-500'];
                        $blobColors = ['hybrid' => '#15803d', 'in_person' => '#FF6B4A', 'online' => '#7C3AED'];
                    @endphp
                    <div class="relative {{ $headerColors[$typeValue] ?? 'bg-coral-900' }} overflow-hidden" style="min-height: 72px;">
                        <x-blob :color="$blobColors[$typeValue] ?? '#FF6B4A'" size="130" opacity="0.15" class="-right-4 -top-4" />
                        <span class="absolute bottom-2 left-2 inline-flex items-center rounded-sm px-2 py-0.5 text-xs font-medium text-white" style="background: rgba(255,255,255,0.15);">
                            {{ $typeValue === 'in_person' ? 'In person' : ($typeValue === 'online' ? 'Online' : 'Hybrid') }}
                        </span>
                    </div>
                    <div class="p-4">
                        <p class="{{ $accentColors[$typeValue] ?? 'text-coral-500' }} font-medium uppercase" style="font-size: 11px; letter-spacing: 0.05em;">
                            {{ $event->starts_at->setTimezone($displayTimezone)->format('D, M j · g:ia') }}
                        </p>
                        <h3 class="mt-1 font-medium text-neutral-900" style="font-size: 15px;">{{ $event->name }}</h3>
                        <p class="mt-0.5 text-neutral-500" style="font-size: 13px;">{{ $event->group->name }}</p>
                        <div class="mt-3 flex items-center gap-1.5">
                            <span class="text-neutral-500" style="font-size: 13px;">{{ $event->rsvps_count ?? $event->rsvps->count() }} going</span>
                        </div>
                    </div>
                </a>
            @endforeach
        </div>
    @endif

    {{-- Infinite scroll trigger --}}
    @if ($hasMorePages)
        <div wire:intersect="loadMore" class="flex justify-center py-8">
            <div wire:loading wire:target="loadMore" class="text-sm text-neutral-500">Loading more events...</div>
        </div>
    @endif

    {{-- Empty state --}}
    @if ($events->isEmpty() && $onlineEvents->isEmpty())
        <div class="py-16 text-center">
            <p class="text-lg font-medium text-neutral-700">No events found</p>
            <p class="mt-2 text-sm text-neutral-500">Try adjusting your filters or search terms.</p>
        </div>
    @endif

    {{-- Online events section --}}
    @if ($onlineEvents->isNotEmpty())
        <div class="mt-12">
            <h2 class="mb-6 font-display text-xl font-medium text-neutral-900">Online Events</h2>
            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($onlineEvents as $event)
                    <a
                        href="{{ route('events.show', [$event->group, $event]) }}"
                        class="block overflow-hidden rounded-xl"
                        style="border: 0.5px solid var(--color-neutral-200)"
                        wire:key="online-{{ $event->id }}"
                    >
                        <div class="relative overflow-hidden bg-violet-900" style="min-height: 72px;">
                            <x-blob color="#7C3AED" size="130" opacity="0.15" class="-right-4 -top-4" />
                            <span class="absolute bottom-2 left-2 inline-flex items-center rounded-sm px-2 py-0.5 text-xs font-medium text-white" style="background: rgba(255,255,255,0.15);">
                                Online
                            </span>
                        </div>
                        <div class="p-4">
                            <p class="font-medium uppercase text-violet-500" style="font-size: 11px; letter-spacing: 0.05em;">
                                {{ $event->starts_at->setTimezone($displayTimezone)->format('D, M j · g:ia') }}
                            </p>
                            <h3 class="mt-1 font-medium text-neutral-900" style="font-size: 15px;">{{ $event->name }}</h3>
                            <p class="mt-0.5 text-neutral-500" style="font-size: 13px;">{{ $event->group->name }}</p>
                            <div class="mt-3 flex items-center gap-1.5">
                                <span class="text-neutral-500" style="font-size: 13px;">{{ $event->rsvps_count ?? $event->rsvps->count() }} going</span>
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>
        </div>
    @endif
</div>
