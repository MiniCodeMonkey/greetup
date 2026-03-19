@props(['title', 'description'])

<div {{ $attributes->merge(['class' => 'relative flex flex-col items-center justify-center text-center py-16 px-6']) }}>
    <x-blob color="#1FAF63" :size="200" :opacity="0.08" class="top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2" />

    <div class="relative z-10">
        <h3 style="font-size: 18px; font-weight: 500; line-height: 1.3;" class="text-neutral-900">{{ $title }}</h3>
        <p style="font-size: 14px;" class="text-neutral-500 mt-2">{{ $description }}</p>

        @if(isset($action))
            <div class="mt-4">
                {{ $action }}
            </div>
        @endif
    </div>
</div>
