@props(['type', 'label' => null])

@php
    $styles = [
        'in_person' => ['bg' => 'bg-coral-50', 'text' => 'text-coral-900'],
        'online' => ['bg' => 'bg-violet-50', 'text' => 'text-violet-900'],
        'hybrid' => ['bg' => 'bg-green-50', 'text' => 'text-green-700'],
        'going' => ['bg' => 'bg-green-50', 'text' => 'text-green-700'],
        'waitlisted' => ['bg' => 'bg-gold-50', 'text' => 'text-gold-900'],
        'cancelled' => ['bg' => 'bg-red-50', 'text' => 'text-red-900'],
        'almost_full' => ['bg' => 'bg-gold-50', 'text' => 'text-gold-900'],
    ];

    $style = $styles[$type] ?? $styles['in_person'];

    $defaultLabels = [
        'in_person' => 'In person',
        'online' => 'Online',
        'hybrid' => 'Hybrid',
        'going' => 'Going',
        'waitlisted' => 'Waitlisted',
        'cancelled' => 'Cancelled',
        'almost_full' => 'Almost full',
    ];

    $displayLabel = $label ?? ($defaultLabels[$type] ?? ucfirst(str_replace('_', ' ', $type)));
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center rounded-sm px-2 py-0.5 text-xs font-medium {$style['bg']} {$style['text']}"]) }}>
    {{ $displayLabel }}
</span>
