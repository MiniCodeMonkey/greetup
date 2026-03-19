@props(['event'])

@php
    $eventType = $event->event_type ?? 'in_person';

    $headerColors = [
        'hybrid' => 'bg-green-900',
        'in_person' => 'bg-coral-900',
        'online' => 'bg-violet-900',
    ];

    $accentColors = [
        'hybrid' => 'text-green-500',
        'in_person' => 'text-coral-500',
        'online' => 'text-violet-500',
    ];

    $blobColors = [
        'hybrid' => '#15803d',
        'in_person' => '#FF6B4A',
        'online' => '#7C3AED',
    ];

    $headerBg = $headerColors[$eventType] ?? $headerColors['in_person'];
    $accentText = $accentColors[$eventType] ?? $accentColors['in_person'];
    $blobColor = $blobColors[$eventType] ?? $blobColors['in_person'];

    $group = $event->group;
    $rsvps = $event->rsvps ?? collect();
    $goingCount = $rsvps->count();
    $capacity = $event->capacity ?? null;

    $almostFull = false;
    $spotsLeft = null;
    $spotsLimited = false;

    if ($capacity && $capacity > 0) {
        $spotsLeft = $capacity - $goingCount;
        $filledPercent = ($goingCount / $capacity) * 100;
        $almostFull = $filledPercent >= 75;
        $spotsLimited = ($spotsLeft / $capacity) < 0.25;
    }

    $eventUrl = $event->url ?? '#';
    $startsAt = $event->starts_at;
@endphp

<a href="{{ $eventUrl }}" {{ $attributes->merge(['class' => 'block rounded-xl overflow-hidden']) }} style="border: 0.5px solid var(--color-neutral-200);">
    {{-- Header --}}
    <div class="relative {{ $headerBg }} overflow-hidden" style="min-height: 72px;">
        <x-blob :color="$blobColor" size="130" opacity="0.15" class="-right-4 -top-4" />

        {{-- Event type pill --}}
        <span class="absolute bottom-2 left-2 inline-flex items-center rounded-sm px-2 py-0.5 text-xs font-medium text-white" style="background: rgba(255,255,255,0.15);">
            {{ $eventType === 'in_person' ? 'In person' : ($eventType === 'online' ? 'Online' : 'Hybrid') }}
        </span>

        {{-- Almost full badge --}}
        @if($almostFull)
            <span class="absolute top-2 right-2">
                <x-badge type="almost_full" />
            </span>
        @endif
    </div>

    {{-- Body --}}
    <div class="p-4">
        {{-- Date --}}
        <p class="{{ $accentText }} font-medium uppercase" style="font-size: 11px; letter-spacing: 0.05em;">
            {{ $startsAt->format('D, M j') }}
        </p>

        {{-- Title --}}
        <h3 class="text-neutral-900 font-medium mt-1" style="font-size: 15px;">
            {{ $event->title }}
        </h3>

        {{-- Group name --}}
        <p class="text-neutral-500 mt-0.5" style="font-size: 13px;">
            {{ $group->name }}
        </p>

        {{-- Attendance row --}}
        <div class="flex items-center justify-between mt-3">
            <div class="flex items-center gap-1.5">
                @if($rsvps->isNotEmpty())
                    <x-avatar-stack :users="$rsvps" :max="3" size="sm" />
                @endif
                <span class="text-neutral-500" style="font-size: 13px;">{{ $goingCount }} going</span>
            </div>

            @if($spotsLeft !== null && $spotsLimited)
                <span class="text-coral-500 font-medium" style="font-size: 13px;">{{ $spotsLeft }} left</span>
            @elseif($spotsLeft !== null)
                <span class="text-neutral-500" style="font-size: 13px;">{{ $spotsLeft }} left</span>
            @endif
        </div>
    </div>
</a>
