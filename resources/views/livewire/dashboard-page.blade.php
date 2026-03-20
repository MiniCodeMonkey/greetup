<div class="mx-auto max-w-6xl px-4 py-8 sm:px-6">
    {{-- Header --}}
    <div class="mb-8">
        <h1 class="font-display text-3xl font-medium text-neutral-900">Dashboard</h1>
    </div>

    {{-- Upcoming Events --}}
    <section class="mb-10">
        <h2 class="mb-4 font-display text-xl font-medium text-neutral-900">Upcoming Events</h2>
        @if ($upcomingEvents->isNotEmpty())
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($upcomingEvents as $event)
                    <a
                        href="{{ route('events.show', [$event->group, $event]) }}"
                        class="block overflow-hidden rounded-xl p-4"
                        style="border: 0.5px solid var(--color-neutral-200)"
                    >
                        @php
                            $typeValue = $event->event_type instanceof \App\Enums\EventType ? $event->event_type->value : $event->event_type;
                            $accentColors = ['hybrid' => 'text-green-500', 'in_person' => 'text-coral-500', 'online' => 'text-violet-500'];
                        @endphp
                        <p class="{{ $accentColors[$typeValue] ?? 'text-coral-500' }} font-medium uppercase" style="font-size: 11px; letter-spacing: 0.05em;">
                            {{ $event->starts_at->format('D, M j · g:ia') }}
                        </p>
                        <h3 class="mt-1 font-medium text-neutral-900" style="font-size: 15px;">{{ $event->name }}</h3>
                        <p class="mt-0.5 text-neutral-500" style="font-size: 13px;">{{ $event->group->name }}</p>
                        @if ($event->venue_name)
                            <p class="mt-1 text-neutral-400" style="font-size: 12px;">{{ $event->venue_name }}</p>
                        @endif
                    </a>
                @endforeach
            </div>
        @else
            <div class="rounded-xl py-8 text-center" style="border: 0.5px solid var(--color-neutral-200)">
                <p class="text-sm text-neutral-500">No upcoming events. Browse the <a href="{{ route('explore') }}" class="font-medium text-green-500 hover:text-green-700">Explore page</a> to find events near you.</p>
            </div>
        @endif
    </section>

    {{-- Your Groups --}}
    <section class="mb-10">
        <h2 class="mb-4 font-display text-xl font-medium text-neutral-900">Your Groups</h2>
        @if ($userGroups->isNotEmpty())
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($userGroups as $group)
                    <a
                        href="{{ route('groups.show', $group) }}"
                        class="block overflow-hidden rounded-xl p-4"
                        style="border: 0.5px solid var(--color-neutral-200)"
                    >
                        <h3 class="font-medium text-neutral-900" style="font-size: 15px;">{{ $group->name }}</h3>
                        @if ($group->events->isNotEmpty())
                            @php $nextEvent = $group->events->first(); @endphp
                            <p class="mt-2 text-green-500" style="font-size: 12px;">
                                Next: {{ $nextEvent->name }}
                            </p>
                            <p class="mt-0.5 text-neutral-400" style="font-size: 12px;">
                                {{ $nextEvent->starts_at->format('D, M j · g:ia') }}
                            </p>
                        @else
                            <p class="mt-2 text-neutral-400" style="font-size: 12px;">No upcoming events</p>
                        @endif
                    </a>
                @endforeach
            </div>
        @else
            <div class="rounded-xl py-8 text-center" style="border: 0.5px solid var(--color-neutral-200)">
                <p class="text-sm text-neutral-500">You have not joined any groups yet. <a href="{{ route('groups.index') }}" class="font-medium text-green-500 hover:text-green-700">Find groups</a> to join.</p>
            </div>
        @endif
    </section>

    {{-- Suggested Events --}}
    <section class="mb-10">
        <h2 class="mb-4 font-display text-xl font-medium text-neutral-900">Suggested Events</h2>
        @if ($suggestedEvents->isNotEmpty())
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($suggestedEvents as $event)
                    <a
                        href="{{ route('events.show', [$event->group, $event]) }}"
                        class="block overflow-hidden rounded-xl p-4"
                        style="border: 0.5px solid var(--color-neutral-200)"
                    >
                        @php
                            $typeValue = $event->event_type instanceof \App\Enums\EventType ? $event->event_type->value : $event->event_type;
                            $accentColors = ['hybrid' => 'text-green-500', 'in_person' => 'text-coral-500', 'online' => 'text-violet-500'];
                        @endphp
                        <p class="{{ $accentColors[$typeValue] ?? 'text-coral-500' }} font-medium uppercase" style="font-size: 11px; letter-spacing: 0.05em;">
                            {{ $event->starts_at->format('D, M j · g:ia') }}
                        </p>
                        <h3 class="mt-1 font-medium text-neutral-900" style="font-size: 15px;">{{ $event->name }}</h3>
                        <p class="mt-0.5 text-neutral-500" style="font-size: 13px;">{{ $event->group->name }}</p>
                    </a>
                @endforeach
            </div>
        @else
            <div class="rounded-xl py-8 text-center" style="border: 0.5px solid var(--color-neutral-200)">
                <p class="text-sm text-neutral-500">No suggestions yet. Join groups and add interests in your <a href="{{ route('settings') }}" class="font-medium text-green-500 hover:text-green-700">settings</a> to get personalized recommendations.</p>
            </div>
        @endif
    </section>

    {{-- Recent Notifications --}}
    <section class="mb-10">
        <h2 class="mb-4 font-display text-xl font-medium text-neutral-900">Recent Notifications</h2>
        @if ($notifications->isNotEmpty())
            <div class="space-y-2">
                @foreach ($notifications as $notification)
                    <div class="rounded-xl p-4" style="border: 0.5px solid var(--color-neutral-200)">
                        <p class="text-sm text-neutral-700">{{ $notification->data['message'] ?? 'New notification' }}</p>
                        <p class="mt-1 text-neutral-400" style="font-size: 12px;">{{ $notification->created_at->diffForHumans() }}</p>
                    </div>
                @endforeach
            </div>
        @else
            <div class="rounded-xl py-8 text-center" style="border: 0.5px solid var(--color-neutral-200)">
                <p class="text-sm text-neutral-500">No unread notifications.</p>
            </div>
        @endif
    </section>
</div>
