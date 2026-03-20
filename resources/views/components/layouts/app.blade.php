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
        @stack('styles')
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

                        {{-- Global search --}}
                        <form action="/search" method="GET" class="relative">
                            <svg class="absolute left-2.5 top-1/2 h-4 w-4 -translate-y-1/2 text-neutral-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                            </svg>
                            <input
                                type="text"
                                name="query"
                                placeholder="Search..."
                                class="w-44 rounded-lg border-neutral-200 py-1.5 pl-9 pr-3 text-sm text-neutral-900 placeholder-neutral-400 focus:border-green-500 focus:ring-green-500"
                                style="border: 0.5px solid var(--color-neutral-200)"
                                data-testid="global-search-input"
                            >
                        </form>
                    </div>

                    @guest
                        {{-- Desktop auth buttons (guest) --}}
                        <div class="hidden items-center gap-3 md:flex">
                            <a href="/login" class="rounded-md px-4 py-2 text-sm font-medium text-neutral-700 hover:text-green-500">Log in</a>
                            <a href="/register" class="rounded-md bg-green-500 px-4 py-2 text-sm font-medium text-white hover:bg-green-700">Sign up</a>
                        </div>
                    @endguest

                    @auth
                        @php
                            $user = auth()->user();
                            $unreadCount = $user->unreadNotifications()->count();
                            $recentNotifications = $user->notifications()->take(10)->get();
                        @endphp

                        {{-- Desktop authenticated controls --}}
                        <div class="hidden items-center gap-4 md:flex">
                            {{-- Notification bell --}}
                            <div class="relative" id="notification-wrapper">
                                <button
                                    type="button"
                                    class="relative rounded-full p-2 text-neutral-500 hover:text-neutral-700"
                                    aria-label="Notifications"
                                    onclick="
                                        const dropdown = document.getElementById('notification-dropdown');
                                        dropdown.classList.toggle('hidden');
                                        document.getElementById('account-dropdown').classList.add('hidden');
                                    "
                                >
                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0" />
                                    </svg>
                                    @if ($unreadCount > 0)
                                        <span class="absolute -right-0.5 -top-0.5 flex h-4 min-w-4 items-center justify-center rounded-full bg-coral-500 px-1 text-[10px] font-medium text-white" data-testid="unread-count">{{ $unreadCount > 99 ? '99+' : $unreadCount }}</span>
                                    @endif
                                </button>

                                {{-- Notification dropdown --}}
                                <div id="notification-dropdown" class="absolute right-0 top-full z-50 mt-2 hidden w-80 rounded-lg bg-white shadow-lg" style="border: 0.5px solid var(--color-neutral-200)">
                                    <div class="px-4 py-3" style="border-bottom: 0.5px solid var(--color-neutral-200)">
                                        <h3 class="text-sm font-medium text-neutral-900">Notifications</h3>
                                    </div>
                                    <div class="max-h-80 overflow-y-auto" data-testid="notification-list">
                                        @forelse ($recentNotifications as $notification)
                                            <div class="px-4 py-3 {{ $notification->read_at ? '' : 'bg-green-50' }}" style="border-bottom: 0.5px solid var(--color-neutral-200)">
                                                <p class="text-sm text-neutral-700">{{ $notification->data['message'] ?? 'New notification' }}</p>
                                                <p class="mt-1 text-xs text-neutral-400">{{ $notification->created_at->diffForHumans() }}</p>
                                            </div>
                                        @empty
                                            <div class="px-4 py-6 text-center">
                                                <p class="text-sm text-neutral-500">No notifications yet</p>
                                            </div>
                                        @endforelse
                                    </div>
                                    @if ($recentNotifications->count() >= 10)
                                        <div class="px-4 py-3 text-center" style="border-top: 0.5px solid var(--color-neutral-200)">
                                            <a href="/notifications" class="text-sm font-medium text-green-500 hover:text-green-700" data-testid="load-more">Load more</a>
                                        </div>
                                    @endif
                                </div>
                            </div>

                            {{-- User avatar dropdown --}}
                            <div class="relative" id="account-wrapper">
                                <button
                                    type="button"
                                    class="flex items-center"
                                    aria-label="Account menu"
                                    onclick="
                                        const dropdown = document.getElementById('account-dropdown');
                                        dropdown.classList.toggle('hidden');
                                        document.getElementById('notification-dropdown').classList.add('hidden');
                                    "
                                >
                                    <x-avatar :user="$user" size="sm" />
                                </button>

                                {{-- Account dropdown --}}
                                <div id="account-dropdown" class="absolute right-0 top-full z-50 mt-2 hidden w-48 rounded-lg bg-white py-1 shadow-lg" style="border: 0.5px solid var(--color-neutral-200)">
                                    <a href="/dashboard" class="block px-4 py-2 text-sm text-neutral-700 hover:bg-neutral-100">Dashboard</a>
                                    <a href="/groups/my" class="block px-4 py-2 text-sm text-neutral-700 hover:bg-neutral-100">My Groups</a>
                                    <a href="/messages" class="block px-4 py-2 text-sm text-neutral-700 hover:bg-neutral-100">Messages</a>
                                    <a href="/settings" class="block px-4 py-2 text-sm text-neutral-700 hover:bg-neutral-100">Settings</a>
                                    <div style="border-top: 0.5px solid var(--color-neutral-200)"></div>
                                    <form method="POST" action="{{ route('logout') }}">
                                        @csrf
                                        <button type="submit" class="block w-full px-4 py-2 text-left text-sm text-neutral-700 hover:bg-neutral-100">Logout</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @endauth

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

                    @guest
                        <div class="mt-3 flex flex-col gap-2 pt-3" style="border-top: 0.5px solid var(--color-neutral-200)">
                            <a href="/login" class="rounded-md px-3 py-2 text-sm font-medium text-neutral-700 hover:bg-neutral-100">Log in</a>
                            <a href="/register" class="rounded-md bg-green-500 px-3 py-2 text-center text-sm font-medium text-white hover:bg-green-700">Sign up</a>
                        </div>
                    @endguest

                    @auth
                        <div class="mt-3 flex flex-col gap-1 pt-3" style="border-top: 0.5px solid var(--color-neutral-200)">
                            <a href="/dashboard" class="block rounded-md px-3 py-2 text-sm font-medium text-neutral-700 hover:bg-neutral-100">Dashboard</a>
                            <a href="/groups/my" class="block rounded-md px-3 py-2 text-sm font-medium text-neutral-700 hover:bg-neutral-100">My Groups</a>
                            <a href="/messages" class="block rounded-md px-3 py-2 text-sm font-medium text-neutral-700 hover:bg-neutral-100">Messages</a>
                            <a href="/notifications" class="block rounded-md px-3 py-2 text-sm font-medium text-neutral-700 hover:bg-neutral-100">
                                Notifications
                                @if ($unreadCount > 0)
                                    <span class="ml-1 inline-flex h-4 min-w-4 items-center justify-center rounded-full bg-coral-500 px-1 text-[10px] font-medium text-white">{{ $unreadCount > 99 ? '99+' : $unreadCount }}</span>
                                @endif
                            </a>
                            <a href="/settings" class="block rounded-md px-3 py-2 text-sm font-medium text-neutral-700 hover:bg-neutral-100">Settings</a>
                        </div>
                        <div class="mt-2 pt-2" style="border-top: 0.5px solid var(--color-neutral-200)">
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="block w-full rounded-md px-3 py-2 text-left text-sm font-medium text-neutral-700 hover:bg-neutral-100">Logout</button>
                            </form>
                        </div>
                    @endauth
                </div>
            </div>
        </nav>

        @auth
            @unless (auth()->user()->hasVerifiedEmail())
                <div class="bg-gold-50 px-4 py-3 text-center text-sm text-gold-900" data-testid="verification-banner">
                    Your email is not verified.
                    <a href="{{ route('verification.notice') }}" class="font-medium underline hover:text-gold-700">Verify your email</a>
                    to join groups and RSVP to events.
                </div>
            @endunless
        @endauth

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
        @stack('scripts')
    </body>
</html>
