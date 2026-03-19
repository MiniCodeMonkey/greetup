@props(['users', 'max' => 5, 'size' => 'sm'])

@if($users->isNotEmpty())
    @php
        $visible = $users->take($max);
        $overflow = $users->count() - $max;

        $sizes = [
            'sm' => ['px' => 24, 'text' => 'text-[10px]'],
            'md' => ['px' => 32, 'text' => 'text-xs'],
            'lg' => ['px' => 44, 'text' => 'text-sm'],
            'xl' => ['px' => 96, 'text' => 'text-2xl'],
        ];

        $sizeConfig = $sizes[$size] ?? $sizes['sm'];
    @endphp

    <div {{ $attributes->merge(['class' => 'flex items-center']) }}>
        @foreach($visible as $index => $user)
            <x-avatar
                :user="$user"
                :size="$size"
                style="border: 2px solid white;{{ $index > 0 ? ' margin-left: -6px;' : '' }}"
            />
        @endforeach

        @if($overflow > 0)
            <div
                class="inline-flex items-center justify-center rounded-pill font-medium bg-neutral-100 text-neutral-500 {{ $sizeConfig['text'] }}"
                style="width: {{ $sizeConfig['px'] }}px; height: {{ $sizeConfig['px'] }}px; margin-left: -6px; border: 2px solid white;"
            >
                +{{ $overflow }}
            </div>
        @endif
    </div>
@endif
