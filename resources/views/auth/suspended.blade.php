<x-layouts.app title="Account Suspended" description="Your account has been suspended.">
    <div class="flex min-h-[60vh] items-center justify-center px-4 py-12">
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

            <form method="POST" action="{{ route('logout') }}" class="mt-8">
                @csrf
                <button type="submit" class="inline-flex items-center rounded-md bg-neutral-100 px-4 py-2.5 text-sm font-medium text-neutral-700 hover:bg-neutral-200 focus:ring-2 focus:ring-neutral-500 focus:ring-offset-2 focus:outline-none">
                    Log out
                </button>
            </form>
        </div>
    </div>
</x-layouts.app>
