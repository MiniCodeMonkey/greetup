<div>
    <h2 class="text-lg font-medium text-neutral-900">Profile Information</h2>
    <p class="mt-1 text-sm text-neutral-500">Update your name and public profile details.</p>

    <form method="POST" action="{{ route('settings.profile.update') }}" class="mt-6 space-y-5">
        @csrf
        @method('PUT')

        <div>
            <label for="name" class="block text-sm font-medium text-neutral-700">Name</label>
            <input
                type="text"
                id="name"
                name="name"
                value="{{ old('name', $user->name) }}"
                required
                class="mt-1 block w-full rounded-md border border-neutral-200 px-3 py-2 text-sm text-neutral-900 placeholder-neutral-400 focus:border-green-500 focus:ring-1 focus:ring-green-500 focus:outline-none"
            />
            @error('name')
                <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <button type="submit" class="rounded-md bg-green-500 px-4 py-2.5 text-sm font-medium text-white hover:bg-green-700 focus:ring-2 focus:ring-green-500 focus:ring-offset-2 focus:outline-none">
                Save profile
            </button>
        </div>
    </form>
</div>
