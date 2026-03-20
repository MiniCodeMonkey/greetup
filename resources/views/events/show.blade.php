<x-layouts.app :title="$seoTitle" :description="$seoDescription" :seoImage="$seoImage" :jsonLd="$jsonLd">
    @php
        $eventTypeValue = $event->event_type instanceof \App\Enums\EventType
            ? $event->event_type->value
            : $event->event_type;

        $accentBg = match ($eventTypeValue) {
            'in_person' => 'bg-coral-900',
            'online' => 'bg-violet-900',
            'hybrid' => 'bg-green-900',
            default => 'bg-coral-900',
        };

        $accentBlobColors = match ($eventTypeValue) {
            'in_person' => ['#FF6B4A', '#E04D2E', '#FF8F76'],
            'online' => ['#7C5CFC', '#5A3CD6', '#9D84FD'],
            'hybrid' => ['#1FAF63', '#178A4E', '#4DC882'],
            default => ['#FF6B4A', '#E04D2E', '#FF8F76'],
        };

        $tz = $event->timezone ?? 'UTC';
        $startsInTz = $event->starts_at->setTimezone($tz);
        $endsInTz = $event->ends_at ? $event->ends_at->setTimezone($tz) : null;

        $tzAbbr = $startsInTz->format('T');
    @endphp

    {{-- Cover band --}}
    <div class="relative w-full overflow-hidden {{ $accentBg }}" style="height: 200px;">
        <x-blob :color="$accentBlobColors[0]" :size="300" :opacity="0.15" shape="cloud" class="left-10 top-4" />
        <x-blob :color="$accentBlobColors[1]" :size="200" :opacity="0.1" shape="circle" class="right-20 top-10" />
        <x-blob :color="$accentBlobColors[2]" :size="250" :opacity="0.1" shape="cloud" class="bottom-0 left-1/3" />
    </div>

    <div class="mx-auto max-w-4xl px-4 py-8">
        @if (session('status'))
            <div class="mb-6 rounded-md bg-green-50 px-4 py-3 text-sm text-green-700">
                {{ session('status') }}
            </div>
        @endif

        {{-- Cancelled notice --}}
        @if ($event->status === \App\Enums\EventStatus::Cancelled)
            <div class="mb-6 rounded-lg bg-red-50 px-4 py-4" data-testid="cancellation-notice">
                <p class="text-sm font-medium text-red-900">This event has been cancelled.</p>
                @if ($event->cancellation_reason)
                    <p class="mt-1 text-sm text-red-900">{{ $event->cancellation_reason }}</p>
                @endif
            </div>
        @endif

        <div class="flex flex-col gap-8 lg:flex-row">
            {{-- Left column --}}
            <div class="min-w-0 flex-1">
                {{-- Date block + title --}}
                <div class="flex items-start gap-4">
                    <x-date-block :date="$startsInTz" :event_type="$eventTypeValue" />
                    <div class="min-w-0 flex-1">
                        <h1 class="text-[22px] font-medium tracking-tight text-neutral-900" style="letter-spacing: -0.02em;">{{ $event->name }}</h1>
                        <p class="mt-1 text-sm text-neutral-500">
                            Hosted by
                            @foreach ($event->hosts as $host)
                                <a href="{{ route('members.show', $host) }}" class="font-medium text-neutral-700 hover:text-green-500">{{ $host->name }}</a>@if (! $loop->last), @endif
                            @endforeach
                        </p>
                    </div>
                </div>

                {{-- Time display --}}
                <div class="mt-4 text-sm text-neutral-700" data-testid="event-time">
                    {{ $startsInTz->format('l, F j') }} at {{ $startsInTz->format('H:i') }} {{ $tzAbbr }}
                    @if ($endsInTz)
                        &ndash; {{ $endsInTz->format('H:i') }} {{ $tzAbbr }}
                    @endif
                </div>

                @if ($userTimezone)
                    @php
                        $userStart = $event->starts_at->setTimezone($userTimezone);
                        $userTzAbbr = $userStart->format('T');
                    @endphp
                    <div class="mt-1 text-sm text-neutral-500" data-testid="user-timezone">
                        {{ $userStart->format('g:i A') }} your time ({{ $userTzAbbr }})
                    </div>
                @endif

                {{-- CTA row --}}
                <div class="mt-6 flex flex-wrap items-center gap-3">
                    @if ($event->status !== \App\Enums\EventStatus::Cancelled)
                        <a href="#" class="inline-flex items-center rounded-md bg-green-500 px-4 py-2 text-sm font-medium text-white hover:bg-green-700" data-testid="rsvp-button">
                            RSVP
                        </a>
                    @endif
                    <a href="{{ route('events.calendar', [$group, $event]) }}" class="inline-flex items-center rounded-md px-4 py-2 text-sm font-medium text-neutral-700 hover:bg-neutral-100" style="border: 0.5px solid var(--color-neutral-200);" data-testid="add-to-calendar">
                        Add to Calendar
                    </a>
                    <button type="button" class="inline-flex items-center rounded-md px-4 py-2 text-sm font-medium text-neutral-700 hover:bg-neutral-100" style="border: 0.5px solid var(--color-neutral-200);" data-testid="share-button" onclick="navigator.share ? navigator.share({title: '{{ e($event->name) }}', url: window.location.href}) : navigator.clipboard.writeText(window.location.href)">
                        Share
                    </button>
                </div>

                {{-- Tab bar --}}
                <div class="mt-8">
                    <x-tab-bar :tabs="[
                        ['label' => 'Details', 'href' => route('events.show', ['group' => $group->slug, 'event' => $event->slug, 'tab' => 'details']), 'active' => $tab === 'details'],
                        ['label' => 'Attendees', 'href' => route('events.show', ['group' => $group->slug, 'event' => $event->slug, 'tab' => 'attendees']), 'active' => $tab === 'attendees'],
                        ['label' => 'Comments', 'href' => route('events.show', ['group' => $group->slug, 'event' => $event->slug, 'tab' => 'comments']), 'active' => $tab === 'comments'],
                        ['label' => 'Chat', 'href' => route('events.show', ['group' => $group->slug, 'event' => $event->slug, 'tab' => 'chat']), 'active' => $tab === 'chat'],
                    ]" />
                </div>

                {{-- Tab content --}}
                <div class="mt-6">
                    @if ($tab === 'details')
                        @if ($event->description_html)
                            <div class="prose prose-sm max-w-none text-neutral-700" data-testid="event-description">
                                {!! $event->description_html !!}
                            </div>
                        @endif

                        @if ($eventTypeValue === 'in_person' || $eventTypeValue === 'hybrid')
                            @if ($event->venue_name)
                                <div class="mt-6" data-testid="venue-info">
                                    <h3 class="text-sm font-medium text-neutral-900">Venue</h3>
                                    <p class="mt-1 text-sm text-neutral-700">{{ $event->venue_name }}</p>
                                    @if ($event->venue_address)
                                        <p class="text-sm text-neutral-500">{{ $event->venue_address }}</p>
                                    @endif
                                </div>
                            @endif
                        @endif

                        @if (($eventTypeValue === 'online' || $eventTypeValue === 'hybrid') && $event->online_link)
                            <div class="mt-6" data-testid="online-link">
                                <h3 class="text-sm font-medium text-neutral-900">Online Link</h3>
                                <a href="{{ $event->online_link }}" target="_blank" rel="noopener" class="mt-1 inline-block text-sm text-green-500 hover:text-green-700">{{ $event->online_link }}</a>
                            </div>
                        @endif
                    @endif

                    @if ($tab === 'attendees')
                        <div data-testid="attendees-tab">
                            <p class="text-sm text-neutral-500">{{ $goingCount }} {{ Str::plural('person', $goingCount) }} going</p>
                            @if ($waitlistCount > 0)
                                <p class="mt-1 text-sm text-neutral-500">{{ $waitlistCount }} on waitlist</p>
                            @endif
                        </div>
                    @endif

                    @if ($tab === 'comments')
                        <p class="text-sm text-neutral-500" data-testid="comments-tab">No comments yet.</p>
                    @endif

                    @if ($tab === 'chat')
                        <p class="text-sm text-neutral-500" data-testid="chat-tab">Chat will be available when the event starts.</p>
                    @endif
                </div>
            </div>

            {{-- Right sidebar --}}
            <div class="w-full space-y-4 lg:w-80 lg:shrink-0">
                {{-- Attendance card --}}
                <div class="rounded-xl bg-neutral-100 p-4" data-testid="attendance-card">
                    <h3 class="text-sm font-medium text-neutral-900">Attendance</h3>
                    <div class="mt-3">
                        <x-progress-bar :current="$goingCount" :max="$event->rsvp_limit" label="Going" />
                    </div>
                    @if ($waitlistCount > 0)
                        <p class="mt-2 text-sm text-neutral-500">{{ $waitlistCount }} on waitlist</p>
                    @endif
                    @if ($attendees->isNotEmpty())
                        <div class="mt-3">
                            <x-avatar-stack :users="$attendees" :max="5" size="sm" />
                        </div>
                    @endif
                </div>

                {{-- Venue card (for in-person/hybrid events) --}}
                @if (($eventTypeValue === 'in_person' || $eventTypeValue === 'hybrid') && $event->venue_name)
                    <div class="rounded-xl bg-neutral-100 p-4" data-testid="venue-card">
                        <h3 class="text-sm font-medium text-neutral-900">Venue</h3>
                        <p class="mt-2 text-sm text-neutral-700">{{ $event->venue_name }}</p>
                        @if ($event->venue_address)
                            <p class="text-sm text-neutral-500">{{ $event->venue_address }}</p>
                        @endif
                        @if ($event->venue_latitude && $event->venue_longitude)
                            <div class="mt-3 overflow-hidden rounded-lg" style="height: 160px;" data-testid="venue-map">
                                <div id="event-map" style="height: 100%; width: 100%;"></div>
                            </div>
                        @endif
                    </div>
                @endif

                {{-- Online link card (for online/hybrid events) --}}
                @if (($eventTypeValue === 'online' || $eventTypeValue === 'hybrid') && $event->online_link)
                    <div class="rounded-xl bg-neutral-100 p-4" data-testid="online-card">
                        <h3 class="text-sm font-medium text-neutral-900">Online</h3>
                        <a href="{{ $event->online_link }}" target="_blank" rel="noopener" class="mt-2 inline-block text-sm text-green-500 hover:text-green-700">Join online</a>
                    </div>
                @endif

                {{-- Hosts card --}}
                <div class="rounded-xl bg-neutral-100 p-4" data-testid="hosts-card">
                    <h3 class="text-sm font-medium text-neutral-900">Hosts</h3>
                    <div class="mt-3 space-y-3">
                        @foreach ($event->hosts as $host)
                            <a href="{{ route('members.show', $host) }}" class="flex items-center gap-3">
                                <x-avatar :user="$host" size="md" />
                                <span class="text-sm font-medium text-neutral-700 hover:text-green-500">{{ $host->name }}</span>
                            </a>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Leaflet map script for in-person/hybrid events --}}
    @if (($eventTypeValue === 'in_person' || $eventTypeValue === 'hybrid') && $event->venue_latitude && $event->venue_longitude)
        @push('styles')
            <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
        @endpush
        @push('scripts')
            <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
            <script>
                document.addEventListener('DOMContentLoaded', function () {
                    const map = L.map('event-map').setView([{{ $event->venue_latitude }}, {{ $event->venue_longitude }}], 15);
                    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                        attribution: '&copy; OpenStreetMap contributors'
                    }).addTo(map);
                    L.marker([{{ $event->venue_latitude }}, {{ $event->venue_longitude }}]).addTo(map);
                });
            </script>
        @endpush
    @endif
</x-layouts.app>
