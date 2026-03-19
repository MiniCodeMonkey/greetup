@props(['value', 'label', 'color' => 'coral'])

@php
    $colors = [
        'coral' => ['bg' => 'bg-coral-500', 'text' => 'text-white'],
        'violet' => ['bg' => 'bg-violet-500', 'text' => 'text-white'],
        'gold' => ['bg' => 'bg-gold-500', 'text' => 'text-neutral-900'],
    ];

    $style = $colors[$color] ?? $colors['coral'];
@endphp

<div {{ $attributes->merge(['class' => "rounded-xl {$style['bg']} {$style['text']}", 'style' => 'padding: 14px;']) }}>
    <div style="font-size: 28px; font-weight: 500; line-height: 1;">{{ $value }}</div>
    <div style="font-size: 11px; opacity: 0.8;">{{ $label }}</div>
</div>
