@props(['color' => '#1FAF63', 'size' => 200, 'opacity' => 0.1, 'shape' => 'cloud'])

<svg
    {{ $attributes->merge(['class' => 'absolute pointer-events-none']) }}
    width="{{ $size }}"
    height="{{ $size }}"
    viewBox="0 0 80 80"
    style="opacity: {{ $opacity }};"
    aria-hidden="true"
>
    @if($shape === 'cloud')
    <path
        d="M40 5 C55 5, 70 15, 72 30 C78 32, 80 38, 78 45
           C80 55, 72 68, 58 70 C52 78, 40 80, 32 74
           C18 76, 5 66, 5 52 C0 42, 5 32, 15 28
           C12 15, 25 5, 40 5Z"
        fill="{{ $color }}"
    />
    @else
    <circle cx="40" cy="40" r="38" fill="{{ $color }}" />
    @endif
</svg>
