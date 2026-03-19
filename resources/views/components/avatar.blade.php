@props(['user', 'size' => 'md'])

@php
    $sizes = [
        'sm' => ['px' => 24, 'text' => 'text-[10px]'],
        'md' => ['px' => 32, 'text' => 'text-xs'],
        'lg' => ['px' => 44, 'text' => 'text-sm'],
        'xl' => ['px' => 96, 'text' => 'text-2xl'],
    ];

    $colors = [
        0 => ['bg' => 'bg-green-500', 'text' => 'text-white'],
        1 => ['bg' => 'bg-coral-500', 'text' => 'text-white'],
        2 => ['bg' => 'bg-violet-500', 'text' => 'text-white'],
        3 => ['bg' => 'bg-gold-500', 'text' => 'text-neutral-900'],
    ];

    $sizeConfig = $sizes[$size] ?? $sizes['md'];
    $colorIndex = $user->id % 4;
    $colorConfig = $colors[$colorIndex];

    $nameParts = preg_split('/\s+/', trim($user->name));
    $initials = mb_strtoupper(mb_substr($nameParts[0], 0, 1));
    if (count($nameParts) > 1) {
        $initials .= mb_strtoupper(mb_substr(end($nameParts), 0, 1));
    }
@endphp

<div
    {{ $attributes->merge(['class' => "inline-flex items-center justify-center rounded-pill font-medium {$colorConfig['bg']} {$colorConfig['text']} {$sizeConfig['text']}"]) }}
    style="width: {{ $sizeConfig['px'] }}px; height: {{ $sizeConfig['px'] }}px;"
>
    {{ $initials }}
</div>
