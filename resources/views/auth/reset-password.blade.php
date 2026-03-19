<x-layouts.app title="Reset Password" description="Set a new password for your Greetup account.">
    <div class="flex min-h-[60vh] items-center justify-center px-4 py-12">
        <div class="w-full max-w-md">
            <h1 class="text-center text-2xl font-medium text-neutral-900">Reset your password</h1>
            <p class="mt-2 text-center text-sm text-neutral-500">Enter your new password below.</p>

            <form method="POST" action="{{ route('password.update') }}" class="mt-8 space-y-5">
                @csrf

                <input type="hidden" name="token" value="{{ $token }}" />

                <div>
                    <label for="email" class="block text-sm font-medium text-neutral-700">Email</label>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        value="{{ old('email', $email) }}"
                        required
                        autofocus
                        class="mt-1 block w-full rounded-md border border-neutral-200 px-3 py-2 text-sm text-neutral-900 placeholder-neutral-400 focus:border-green-500 focus:ring-1 focus:ring-green-500 focus:outline-none"
                        placeholder="you@example.com"
                    />
                    @error('email')
                        <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="password" class="block text-sm font-medium text-neutral-700">New password</label>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        required
                        class="mt-1 block w-full rounded-md border border-neutral-200 px-3 py-2 text-sm text-neutral-900 placeholder-neutral-400 focus:border-green-500 focus:ring-1 focus:ring-green-500 focus:outline-none"
                        placeholder="At least 8 characters"
                    />
                    @error('password')
                        <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="password_confirmation" class="block text-sm font-medium text-neutral-700">Confirm new password</label>
                    <input
                        type="password"
                        id="password_confirmation"
                        name="password_confirmation"
                        required
                        class="mt-1 block w-full rounded-md border border-neutral-200 px-3 py-2 text-sm text-neutral-900 placeholder-neutral-400 focus:border-green-500 focus:ring-1 focus:ring-green-500 focus:outline-none"
                        placeholder="Repeat your new password"
                    />
                </div>

                <button type="submit" class="w-full rounded-md bg-green-500 px-4 py-2.5 text-sm font-medium text-white hover:bg-green-700 focus:ring-2 focus:ring-green-500 focus:ring-offset-2 focus:outline-none">
                    Reset password
                </button>
            </form>
        </div>
    </div>
</x-layouts.app>
