<x-layouts.app :title="$seoTitle" :description="$seoDescription" :seoImage="$seoImage" seoType="profile">
    <div class="mx-auto max-w-3xl px-4 py-10">
        {{-- Profile header --}}
        <div class="flex flex-col items-center text-center sm:flex-row sm:items-start sm:text-left">
            <div class="shrink-0">
                <x-avatar :user="$member" size="xl" />
            </div>
            <div class="mt-4 sm:ml-6 sm:mt-0">
                <h1 class="text-2xl font-medium text-neutral-900">{{ $member->name }}</h1>

                @if ($member->location)
                    <p class="mt-1 text-sm text-neutral-500">{{ $member->location }}</p>
                @endif

                @auth
                    @if (auth()->id() !== $member->id)
                        <div class="mt-4 flex items-center gap-3">
                            <form action="{{ route('messages.store') }}" method="POST">
                                @csrf
                                <input type="hidden" name="recipient_id" value="{{ $member->id }}">
                                <button type="submit" class="inline-flex items-center rounded-md bg-green-500 px-4 py-2 text-sm font-medium text-white hover:bg-green-700">
                                    Message
                                </button>
                            </form>

                            <div class="relative" id="profile-actions-wrapper">
                                <button
                                    type="button"
                                    class="inline-flex items-center rounded-md px-3 py-2 text-sm font-medium text-neutral-700 hover:bg-neutral-100"
                                    style="border: 0.5px solid var(--color-neutral-200)"
                                    aria-label="More actions"
                                    onclick="document.getElementById('profile-actions-dropdown').classList.toggle('hidden')"
                                >
                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 12a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0ZM12.75 12a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0ZM18.75 12a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Z" />
                                    </svg>
                                </button>

                                <div id="profile-actions-dropdown" class="absolute left-0 top-full z-50 mt-1 hidden w-40 rounded-lg bg-white py-1 shadow-lg" style="border: 0.5px solid var(--color-neutral-200)">
                                    <a href="/members/{{ $member->id }}/report" class="block px-4 py-2 text-sm text-neutral-700 hover:bg-neutral-100" data-testid="report-button">Report</a>
                                    <a href="/members/{{ $member->id }}/block" class="block px-4 py-2 text-sm text-red-500 hover:bg-neutral-100" data-testid="block-button">Block</a>
                                </div>
                            </div>
                        </div>
                    @endif
                @endauth
            </div>
        </div>

        {{-- Bio --}}
        @if ($member->bio)
            <div class="mt-8">
                <h2 class="text-sm font-medium text-neutral-500">About</h2>
                <p class="mt-2 text-sm leading-relaxed text-neutral-700">{{ $member->bio }}</p>
            </div>
        @endif

        {{-- Interests --}}
        @if ($interests->isNotEmpty())
            <div class="mt-8">
                <h2 class="text-sm font-medium text-neutral-500">Interests</h2>
                <div class="mt-2 flex flex-wrap gap-2">
                    @foreach ($interests as $interest)
                        <span class="inline-flex items-center rounded-pill bg-green-50 px-3 py-1 text-xs font-medium text-green-700">{{ $interest->name }}</span>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Looking for --}}
        @if ($member->looking_for && count($member->looking_for) > 0)
            <div class="mt-8">
                <h2 class="text-sm font-medium text-neutral-500">Looking for</h2>
                <div class="mt-2 flex flex-wrap gap-2">
                    @foreach ($member->looking_for as $item)
                        <span class="inline-flex items-center rounded-pill bg-violet-50 px-3 py-1 text-xs font-medium text-violet-900">{{ ucfirst($item) }}</span>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Groups in common --}}
        @auth
            @if ($commonGroups->isNotEmpty())
                <div class="mt-8">
                    <h2 class="text-sm font-medium text-neutral-500">Groups in common</h2>
                    <div class="mt-2 space-y-3">
                        @foreach ($commonGroups as $group)
                            <a href="/groups/{{ $group->slug }}" class="block rounded-lg px-4 py-3 text-sm font-medium text-neutral-700 hover:bg-neutral-100" style="border: 0.5px solid var(--color-neutral-200)">
                                {{ $group->name }}
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif
        @endauth
    </div>
</x-layouts.app>
