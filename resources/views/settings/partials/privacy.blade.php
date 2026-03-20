<div>
    <h2 class="text-lg font-medium text-neutral-900">Privacy Settings</h2>
    <p class="mt-1 text-sm text-neutral-500">Control your profile visibility and data.</p>

    <form method="POST" action="{{ route('settings.privacy.update') }}" class="mt-6 space-y-5">
        @csrf
        @method('PUT')

        <fieldset>
            <legend class="text-sm font-medium text-neutral-700">Profile Visibility</legend>
            <p class="mt-1 text-sm text-neutral-500">Choose who can see your profile.</p>

            <div class="mt-4 space-y-3">
                <label class="flex items-start gap-3 cursor-pointer">
                    <input
                        type="radio"
                        name="profile_visibility"
                        value="public"
                        @checked(old('profile_visibility', $user->profile_visibility->value) === 'public')
                        class="mt-0.5 h-4 w-4 border-neutral-400 text-green-500 focus:ring-green-500"
                    />
                    <div>
                        <span class="text-sm font-medium text-neutral-900">Public</span>
                        <p class="text-sm text-neutral-500">Anyone can view your profile and find you in search.</p>
                    </div>
                </label>

                <label class="flex items-start gap-3 cursor-pointer">
                    <input
                        type="radio"
                        name="profile_visibility"
                        value="members_only"
                        @checked(old('profile_visibility', $user->profile_visibility->value) === 'members_only')
                        class="mt-0.5 h-4 w-4 border-neutral-400 text-green-500 focus:ring-green-500"
                    />
                    <div>
                        <span class="text-sm font-medium text-neutral-900">Members Only</span>
                        <p class="text-sm text-neutral-500">Only users who share a group with you can view your profile. Your profile will not appear in search results.</p>
                    </div>
                </label>
            </div>

            @error('profile_visibility')
                <p class="mt-2 text-sm text-red-500">{{ $message }}</p>
            @enderror
        </fieldset>

        <div>
            <button type="submit" class="rounded-md bg-green-500 px-4 py-2.5 text-sm font-medium text-white hover:bg-green-700 focus:ring-2 focus:ring-green-500 focus:ring-offset-2 focus:outline-none">
                Save privacy settings
            </button>
        </div>
    </form>
</div>
