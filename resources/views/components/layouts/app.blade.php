@props([
    'title' => config('app.name', 'Greetup'),
    'description' => 'Find your people. Do the thing. Keep showing up.',
    'seoImage' => null,
    'seoType' => 'website',
    'canonicalUrl' => null,
    'jsonLd' => null,
])
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Instrument+Sans:wght@400;500&display=swap" rel="stylesheet">

        <x-seo
            :title="$title"
            :description="$description"
            :image="$seoImage"
            :type="$seoType"
            :canonicalUrl="$canonicalUrl"
            :jsonLd="$jsonLd"
        />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-neutral-50 font-body text-neutral-900 antialiased">
        <nav class="sticky top-0 z-50 bg-white" style="border-bottom: 0.5px solid var(--color-neutral-200)">
            <div class="mx-auto max-w-6xl px-4 sm:px-6">
                <div class="flex h-16 items-center justify-between">
                    {{-- Logo --}}
                    <a href="/" class="flex shrink-0 items-center gap-2">
                        <img src="{{ asset('images/greetup.png') }}" alt="Greetup" class="h-8">
                    </a>

                    {{-- Desktop nav links --}}
                    <div class="hidden items-center gap-6 md:flex">
                        <a href="/explore" class="text-sm font-medium text-neutral-700 hover:text-green-500">Explore</a>
                        <a href="/groups" class="text-sm font-medium text-neutral-700 hover:text-green-500">Groups</a>
                    </div>

                    {{-- Desktop auth buttons --}}
                    <div class="hidden items-center gap-3 md:flex">
                        <a href="/login" class="rounded-md px-4 py-2 text-sm font-medium text-neutral-700 hover:text-green-500">Log in</a>
                        <a href="/register" class="rounded-md bg-green-500 px-4 py-2 text-sm font-medium text-white hover:bg-green-700">Sign up</a>
                    </div>

                    {{-- Mobile hamburger button --}}
                    <button
                        type="button"
                        class="inline-flex items-center justify-center rounded-md p-2 text-neutral-500 hover:text-neutral-700 md:hidden"
                        aria-label="Toggle navigation"
                        aria-expanded="false"
                        onclick="
                            const menu = document.getElementById('mobile-menu');
                            const expanded = this.getAttribute('aria-expanded') === 'true';
                            this.setAttribute('aria-expanded', !expanded);
                            menu.classList.toggle('hidden');
                        "
                    >
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                        </svg>
                    </button>
                </div>
            </div>

            {{-- Mobile menu --}}
            <div id="mobile-menu" class="hidden md:hidden" style="border-top: 0.5px solid var(--color-neutral-200)">
                <div class="space-y-1 px-4 pb-4 pt-2">
                    <a href="/explore" class="block rounded-md px-3 py-2 text-sm font-medium text-neutral-700 hover:bg-neutral-100">Explore</a>
                    <a href="/groups" class="block rounded-md px-3 py-2 text-sm font-medium text-neutral-700 hover:bg-neutral-100">Groups</a>
                    <div class="mt-3 flex flex-col gap-2 pt-3" style="border-top: 0.5px solid var(--color-neutral-200)">
                        <a href="/login" class="rounded-md px-3 py-2 text-sm font-medium text-neutral-700 hover:bg-neutral-100">Log in</a>
                        <a href="/register" class="rounded-md bg-green-500 px-3 py-2 text-center text-sm font-medium text-white hover:bg-green-700">Sign up</a>
                    </div>
                </div>
            </div>
        </nav>

        <main>
            {{ $slot }}
        </main>

        <footer class="mt-16" style="border-top: 0.5px solid var(--color-neutral-200)">
            <div class="mx-auto max-w-6xl px-4 py-8 sm:px-6">
                <div class="flex flex-col items-center justify-between gap-4 sm:flex-row">
                    <a href="/" class="flex items-center gap-2">
                        <img src="{{ asset('images/greetup.png') }}" alt="Greetup" class="h-6">
                    </a>
                    <p class="text-xs text-neutral-500">&copy; {{ date('Y') }} {{ config('app.name', 'Greetup') }}. All rights reserved.</p>
                </div>
            </div>
        </footer>
    </body>
</html>
