<x-layouts.app title="Forgot Password" description="Reset your Greetup account password.">
    <div class="flex min-h-[60vh] items-center justify-center px-4 py-12">
        <div class="w-full max-w-md">
            <h1 class="text-center text-2xl font-medium text-neutral-900">Forgot your password?</h1>
            <p class="mt-2 text-center text-sm text-neutral-500">No worries. Enter your email and we'll send you a reset link.</p>

            @if (session('status'))
                <div class="mt-4 rounded-md bg-green-50 p-3 text-center text-sm text-green-700">
                    {{ session('status') }}
                </div>
            @endif

            <form method="POST" action="{{ route('password.email') }}" class="mt-8 space-y-5">
                @csrf

                <div>
                    <label for="email" class="block text-sm font-medium text-neutral-700">Email</label>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        value="{{ old('email') }}"
                        required
                        autofocus
                        class="mt-1 block w-full rounded-md border border-neutral-200 px-3 py-2 text-sm text-neutral-900 placeholder-neutral-400 focus:border-green-500 focus:ring-1 focus:ring-green-500 focus:outline-none"
                        placeholder="you@example.com"
                    />
                    @error('email')
                        <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <button type="submit" class="w-full rounded-md bg-green-500 px-4 py-2.5 text-sm font-medium text-white hover:bg-green-700 focus:ring-2 focus:ring-green-500 focus:ring-offset-2 focus:outline-none">
                    Send reset link
                </button>

                <p class="text-center text-sm text-neutral-500">
                    Remember your password?
                    <a href="{{ route('login') }}" class="font-medium text-green-500 hover:text-green-700">Log in</a>
                </p>
            </form>
        </div>
    </div>
</x-layouts.app>
