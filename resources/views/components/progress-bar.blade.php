@props(['current', 'max' => null, 'label' => null])

@php
    $hasMax = !is_null($max);
    $percentage = $hasMax && $max > 0 ? min(100, round(($current / $max) * 100)) : 0;
    $remaining = $hasMax ? max(0, $max - $current) : null;
    $remainingPercent = $hasMax && $max > 0 ? ($remaining / $max) * 100 : 100;
    $isUrgent = $hasMax && $remainingPercent < 25;
    $remainingColor = $isUrgent ? 'text-coral-500' : 'text-neutral-500';
@endphp

<div {{ $attributes }}>
    {{-- Label --}}
    @if($label)
        <div class="text-neutral-500" style="font-size: 14px; margin-bottom: 4px;">{{ $label }}</div>
    @endif

    {{-- Count display --}}
    <div class="flex items-baseline gap-1">
        <span style="font-size: 24px; font-weight: 500; line-height: 1;">{{ $current }}</span>
        @if($hasMax)
            <span class="text-neutral-500" style="font-size: 14px;">/ {{ $max }}</span>
        @endif
    </div>

    {{-- Progress track --}}
    @if($hasMax)
        <div class="bg-neutral-100 rounded-full" style="height: 6px; margin-top: 8px;">
            <div class="bg-green-500 rounded-full" style="height: 100%; width: {{ $percentage }}%;"></div>
        </div>

        {{-- Remaining text --}}
        <div class="{{ $remainingColor }}" style="font-size: 14px; margin-top: 4px;">
            {{ $remaining }} {{ $remaining === 1 ? 'spot' : 'spots' }} remaining
        </div>
    @endif
</div>
