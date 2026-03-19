<x-layouts.app title="Log In" description="Log in to your Greetup account.">
    <div class="flex min-h-[60vh] items-center justify-center px-4 py-12">
        <div class="w-full max-w-md">
            <h1 class="text-center text-2xl font-medium text-neutral-900">Log in to your account</h1>
            <p class="mt-2 text-center text-sm text-neutral-500">Welcome back! Enter your credentials to continue.</p>

            <form method="POST" action="{{ route('login') }}" class="mt-8 space-y-5">
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

                <div>
                    <label for="password" class="block text-sm font-medium text-neutral-700">Password</label>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        required
                        class="mt-1 block w-full rounded-md border border-neutral-200 px-3 py-2 text-sm text-neutral-900 placeholder-neutral-400 focus:border-green-500 focus:ring-1 focus:ring-green-500 focus:outline-none"
                        placeholder="Your password"
                    />
                </div>

                <div class="flex items-center">
                    <input
                        type="checkbox"
                        id="remember"
                        name="remember"
                        class="size-4 rounded border-neutral-300 text-green-500 focus:ring-green-500"
                    />
                    <label for="remember" class="ml-2 text-sm text-neutral-500">Remember me</label>
                </div>

                <button type="submit" class="w-full rounded-md bg-green-500 px-4 py-2.5 text-sm font-medium text-white hover:bg-green-700 focus:ring-2 focus:ring-green-500 focus:ring-offset-2 focus:outline-none">
                    Log in
                </button>

                <p class="text-center text-sm text-neutral-500">
                    Don't have an account?
                    <a href="{{ route('register') }}" class="font-medium text-green-500 hover:text-green-700">Sign up</a>
                </p>
            </form>
        </div>
    </div>
</x-layouts.app>
