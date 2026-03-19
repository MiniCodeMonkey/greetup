@props(['date', 'event_type' => 'in_person'])

@php
    $accents = [
        'in_person' => ['bg' => 'bg-coral-50', 'month' => 'text-coral-500', 'day' => 'text-coral-900'],
        'online' => ['bg' => 'bg-violet-50', 'month' => 'text-violet-500', 'day' => 'text-violet-900'],
        'hybrid' => ['bg' => 'bg-violet-50', 'month' => 'text-violet-500', 'day' => 'text-violet-900'],
    ];

    $accent = $accents[$event_type] ?? $accents['in_person'];

    $month = mb_strtoupper($date->format('M'));
    $day = $date->format('j');
@endphp

<div {{ $attributes->merge(['class' => "inline-flex flex-col items-center justify-center rounded-lg p-2 {$accent['bg']}", 'style' => 'width: 56px;']) }}>
    <span class="text-[11px] uppercase leading-tight font-medium {{ $accent['month'] }}">{{ $month }}</span>
    <span class="text-[24px] font-medium leading-tight {{ $accent['day'] }}">{{ $day }}</span>
</div>
