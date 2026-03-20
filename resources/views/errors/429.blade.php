<x-layouts.app title="429 - Too Many Requests">
    <div class="relative flex min-h-[calc(100vh-12rem)] items-center justify-center px-4">
        <x-blob class="left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2" color="var(--color-green-500)" :size="480" :opacity="0.06" />

        <div class="relative z-10 text-center">
            <p class="text-[44px] font-medium leading-none text-neutral-400">429</p>
            <h1 class="mt-4 text-[22px] font-medium text-neutral-900">Slow down</h1>
            <p class="mt-2 text-base text-neutral-500">You're making requests too quickly. Please wait a moment and try again.</p>
            <a href="/explore" class="mt-8 inline-block rounded-md bg-green-500 px-6 py-2.5 text-sm font-medium text-white hover:bg-green-700">Go to Explore</a>
        </div>
    </div>
</x-layouts.app>
