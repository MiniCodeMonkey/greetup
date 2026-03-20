<x-layouts.app :title="$title" :description="$description">
    <div class="relative flex min-h-[calc(100vh-12rem)] items-center justify-center px-4">
        <x-blob class="left-1/4 top-1/3 -translate-x-1/2 -translate-y-1/2" color="var(--color-green-500)" :size="420" :opacity="0.06" />
        <x-blob class="right-1/4 bottom-1/3 translate-x-1/2 translate-y-1/2" color="var(--color-coral-500)" :size="320" :opacity="0.05" />

        <div class="relative z-10 text-center">
            <h1 class="text-4xl font-medium tracking-tight text-neutral-900 sm:text-5xl">Find your people</h1>
            <p class="mx-auto mt-4 max-w-lg text-lg text-neutral-500">{{ $description }}</p>
            <div class="mt-8 flex items-center justify-center gap-4">
                <a href="/explore" class="rounded-md bg-green-500 px-6 py-2.5 text-sm font-medium text-white hover:bg-green-700">Explore Events</a>
                <a href="/groups" class="rounded-md border px-6 py-2.5 text-sm font-medium text-neutral-700 hover:bg-neutral-100" style="border-color: var(--color-neutral-200)">Browse Groups</a>
            </div>
        </div>
    </div>
</x-layouts.app>
