@props(['tag' => null, 'name' => null, 'id' => null])

@php
    $pillId = $tag->id ?? $id ?? 0;
    $pillName = $tag->name ?? $name ?? '';

    $colorIndex = $pillId % 4;

    $styles = [
        0 => ['bg' => 'bg-green-50', 'text' => 'text-green-700'],
        1 => ['bg' => 'bg-coral-50', 'text' => 'text-coral-900'],
        2 => ['bg' => 'bg-violet-50', 'text' => 'text-violet-900'],
        3 => ['bg' => 'bg-gold-50', 'text' => 'text-gold-900'],
    ];

    $style = $styles[$colorIndex];
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center rounded-pill px-3 py-1 text-xs font-medium {$style['bg']} {$style['text']}"]) }}>
    {{ $pillName }}
</span>
