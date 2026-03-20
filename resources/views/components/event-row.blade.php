@props(['event', 'show_rsvp' => true, 'displayTimezone' => null])

@php
    $eventType = $event->event_type ?? 'in_person';
    $startsAt = $displayTimezone ? $event->starts_at->setTimezone($displayTimezone) : $event->starts_at;
    $rsvps = $event->rsvps ?? collect();
    $goingCount = $rsvps->count();
    $capacity = $event->capacity ?? null;
    $venue = $event->venue ?? null;

    $almostFull = false;
    $plentyOfSpots = true;

    if ($capacity && $capacity > 0) {
        $filledPercent = ($goingCount / $capacity) * 100;
        $almostFull = $filledPercent >= 75;
        $plentyOfSpots = $filledPercent < 75;
    }

    $metaParts = [
        $startsAt->format('l'),
        $startsAt->format('H:i'),
    ];

    if ($venue) {
        $metaParts[] = $venue;
    }

    $metaLine = implode(' · ', $metaParts);

    $isPrimary = $plentyOfSpots;
@endphp

<div {{ $attributes->merge(['class' => 'flex flex-col md:flex-row md:items-center gap-3 md:gap-4']) }}>
    {{-- Date block --}}
    <div class="shrink-0">
        <x-date-block :date="$startsAt" :event_type="$eventType" />
    </div>

    {{-- Content --}}
    <div class="flex-1 min-w-0">
        {{-- Title --}}
        <h3 class="text-neutral-900 font-medium truncate" style="font-size: 15px;">
            {{ $event->title }}
        </h3>

        {{-- Meta line --}}
        <p class="text-neutral-500 mt-0.5" style="font-size: 13px;">
            {{ $metaLine }}
        </p>

        {{-- Badges row --}}
        <div class="flex items-center gap-2 mt-1.5 flex-wrap">
            <x-badge :type="$eventType" />
            <span class="text-neutral-500" style="font-size: 13px;">{{ $goingCount }} going</span>
            @if($almostFull)
                <x-badge type="almost_full" />
            @endif
        </div>
    </div>

    {{-- RSVP button --}}
    @if($show_rsvp)
        <div class="shrink-0 md:self-center">
            @if($isPrimary)
                <button type="button" class="w-full md:w-auto rounded-md font-medium text-white bg-green-500 hover:bg-green-700" style="font-size: 13px; padding: 8px 20px;">
                    RSVP
                </button>
            @else
                <button type="button" class="w-full md:w-auto rounded-md font-medium text-green-500 bg-transparent hover:bg-green-50" style="font-size: 13px; padding: 8px 20px; border: 1.5px solid var(--color-green-500);">
                    RSVP
                </button>
            @endif
        </div>
    @endif
</div>
