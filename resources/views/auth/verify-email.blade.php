<x-layouts.app title="Verify Your Email" description="Please verify your email address to continue.">
    <div class="flex min-h-[60vh] items-center justify-center px-4 py-12">
        <div class="w-full max-w-md text-center">
            <h1 class="text-2xl font-medium text-neutral-900">Check your email</h1>
            <p class="mt-2 text-sm text-neutral-500">
                We've sent a verification link to your email address. Please check your inbox and click the link to verify your account.
            </p>

            @if (session('status') === 'verification-link-sent')
                <div class="mt-4 rounded-md bg-green-50 p-3 text-sm text-green-700">
                    A new verification link has been sent to your email address.
                </div>
            @endif

            <form method="POST" action="{{ route('verification.send') }}" class="mt-6">
                @csrf
                <button type="submit" class="rounded-md bg-green-500 px-4 py-2.5 text-sm font-medium text-white hover:bg-green-700 focus:ring-2 focus:ring-green-500 focus:ring-offset-2 focus:outline-none">
                    Resend verification email
                </button>
            </form>
        </div>
    </div>
</x-layouts.app>
