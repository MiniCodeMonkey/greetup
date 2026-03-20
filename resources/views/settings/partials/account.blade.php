<div class="space-y-10">
    {{-- Email Update --}}
    <div>
        <h2 class="text-lg font-medium text-neutral-900">Email Address</h2>
        <p class="mt-1 text-sm text-neutral-500">Update your email address. You will need to re-verify your new email.</p>

        <form method="POST" action="{{ route('settings.account.update') }}" class="mt-6 space-y-5">
            @csrf
            @method('PUT')

            <div>
                <label for="email" class="block text-sm font-medium text-neutral-700">Email</label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    value="{{ old('email', $user->email) }}"
                    required
                    class="mt-1 block w-full rounded-md border border-neutral-200 px-3 py-2 text-sm text-neutral-900 placeholder-neutral-400 focus:border-green-500 focus:ring-1 focus:ring-green-500 focus:outline-none"
                />
                @error('email')
                    <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <button type="submit" class="rounded-md bg-green-500 px-4 py-2.5 text-sm font-medium text-white hover:bg-green-700 focus:ring-2 focus:ring-green-500 focus:ring-offset-2 focus:outline-none">
                    Update email
                </button>
            </div>
        </form>
    </div>

    <hr class="border-neutral-200" />

    {{-- Password Update --}}
    <div>
        <h2 class="text-lg font-medium text-neutral-900">Update Password</h2>
        <p class="mt-1 text-sm text-neutral-500">Ensure your account is using a strong password.</p>

        <form method="POST" action="{{ route('settings.account.update') }}" class="mt-6 space-y-5">
            @csrf
            @method('PUT')

            <div>
                <label for="current_password" class="block text-sm font-medium text-neutral-700">Current password</label>
                <input
                    type="password"
                    id="current_password"
                    name="current_password"
                    required
                    class="mt-1 block w-full rounded-md border border-neutral-200 px-3 py-2 text-sm text-neutral-900 placeholder-neutral-400 focus:border-green-500 focus:ring-1 focus:ring-green-500 focus:outline-none"
                />
                @error('current_password')
                    <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="password" class="block text-sm font-medium text-neutral-700">New password</label>
                <input
                    type="password"
                    id="password"
                    name="password"
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
                    class="mt-1 block w-full rounded-md border border-neutral-200 px-3 py-2 text-sm text-neutral-900 placeholder-neutral-400 focus:border-green-500 focus:ring-1 focus:ring-green-500 focus:outline-none"
                />
            </div>

            <div>
                <button type="submit" class="rounded-md bg-green-500 px-4 py-2.5 text-sm font-medium text-white hover:bg-green-700 focus:ring-2 focus:ring-green-500 focus:ring-offset-2 focus:outline-none">
                    Update password
                </button>
            </div>
        </form>
    </div>
</div>
