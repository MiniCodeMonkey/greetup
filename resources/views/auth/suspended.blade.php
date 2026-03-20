@props([])
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>Account Suspended - {{ config('app.name', 'Greetup') }}</title>

        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Instrument+Sans:wght@400;500&display=swap" rel="stylesheet">

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-neutral-50 font-body text-neutral-900 antialiased">
        <nav class="sticky top-0 z-50 bg-white" style="border-bottom: 0.5px solid var(--color-neutral-200)">
            <div class="mx-auto max-w-6xl px-4 sm:px-6">
                <div class="flex h-16 items-center justify-between">
                    <a href="/" class="flex shrink-0 items-center gap-2">
                        <img src="{{ asset('images/greetup.png') }}" alt="Greetup" class="h-8">
                    </a>

                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="text-sm font-medium text-neutral-700 hover:text-red-500">Log out</button>
                    </form>
                </div>
            </div>
        </nav>

        <main>
            <div class="flex min-h-[calc(100vh-12rem)] items-center justify-center px-4 py-12">
                <div class="w-full max-w-md text-center">
                    <div class="mx-auto flex size-16 items-center justify-center rounded-full bg-red-50">
                        <svg class="size-8 text-red-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                        </svg>
                    </div>

                    <h1 class="mt-6 text-2xl font-medium text-neutral-900">Your account has been suspended</h1>

                    @if(auth()->user()->suspended_reason)
                        <p class="mt-3 text-sm text-neutral-500">{{ auth()->user()->suspended_reason }}</p>
                    @endif

                    <div class="mt-8 flex flex-col items-center gap-4">
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="inline-flex items-center rounded-md bg-neutral-100 px-4 py-2.5 text-sm font-medium text-neutral-700 hover:bg-neutral-200 focus:ring-2 focus:ring-neutral-500 focus:ring-offset-2 focus:outline-none">
                                Log out
                            </button>
                        </form>

                        @if(\App\Models\Setting::get('support_url'))
                            <a href="{{ \App\Models\Setting::get('support_url') }}" class="text-sm font-medium text-red-500 hover:text-red-700">Contact support</a>
                        @endif
                    </div>
                </div>
            </div>
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
