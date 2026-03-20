<x-layouts.app :title="$title" :description="$description">
    {{-- Hero Section --}}
    <div class="relative overflow-hidden">
        {{-- Decorative blobs --}}
        <x-blob class="left-1/4 top-1/4 -translate-x-1/2 -translate-y-1/2" color="var(--color-green-500)" :size="420" :opacity="0.06" />
        <x-blob class="right-1/3 top-1/3 translate-x-1/2" color="var(--color-coral-500)" :size="320" :opacity="0.05" />
        <x-blob class="left-2/3 bottom-1/4 translate-y-1/2" color="var(--color-violet-500)" :size="360" :opacity="0.05" shape="circle" />

        <div class="relative z-10 mx-auto max-w-6xl px-4 pb-20 pt-16 sm:px-6 sm:pt-24 lg:pt-32">
            <div class="flex flex-col lg:flex-row lg:items-end lg:justify-between">
                {{-- Headline + CTA --}}
                <div class="max-w-xl">
                    <h1 style="font-size: 44px; font-weight: 500; letter-spacing: -0.03em; line-height: 1.15;">
                        <span class="text-neutral-900">Find your </span><span class="text-green-500">people.</span><br>
                        <span class="text-neutral-900">Do the </span><span class="text-coral-500">thing.</span><br>
                        <span class="text-neutral-900">Keep </span><span class="text-violet-500">showing up.</span>
                    </h1>

                    <p class="mt-4 max-w-lg text-lg text-neutral-500">{{ $description }}</p>

                    <div class="mt-8 flex items-center gap-4">
                        <a href="{{ route('register') }}" class="rounded-md bg-green-500 px-6 py-2.5 text-sm font-medium text-white hover:bg-green-700">Get started</a>
                        <a href="{{ route('explore') }}" class="rounded-md border px-6 py-2.5 text-sm font-medium text-neutral-700 hover:bg-neutral-100" style="border-color: var(--color-neutral-200);">Explore events</a>
                    </div>
                </div>

                {{-- Stat cards --}}
                <div class="mt-10 flex gap-3 lg:mt-0">
                    <x-stat-card :value="number_format($stats['groups'])" label="Groups" color="coral" />
                    <x-stat-card :value="number_format($stats['events'])" label="Events" color="violet" />
                    <x-stat-card :value="number_format($stats['members'])" label="Members" color="gold" />
                </div>
            </div>
        </div>
    </div>

    {{-- Popular Interests --}}
    @if($interests->isNotEmpty())
        <div class="mx-auto max-w-6xl px-4 py-12 sm:px-6">
            <h2 class="text-lg font-medium text-neutral-900">Popular interests</h2>
            <div class="mt-4 flex flex-wrap gap-2">
                @foreach($interests as $interest)
                    <x-pill :tag="$interest" />
                @endforeach
            </div>
        </div>
    @endif

    {{-- Upcoming Events --}}
    @if($upcomingEvents->isNotEmpty())
        <div class="mx-auto max-w-6xl px-4 pb-20 sm:px-6">
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-medium text-neutral-900">Upcoming events</h2>
                <a href="{{ route('explore') }}" class="text-sm font-medium text-green-500 hover:text-green-700">View all</a>
            </div>
            <div class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach($upcomingEvents as $event)
                    <x-event-card :event="$event" />
                @endforeach
            </div>
        </div>
    @endif
</x-layouts.app>
