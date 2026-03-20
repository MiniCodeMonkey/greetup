<div>
    <h2 class="text-lg font-medium text-neutral-900">Profile Information</h2>
    <p class="mt-1 text-sm text-neutral-500">Update your name and public profile details.</p>

    <form method="POST" action="{{ route('settings.profile.update') }}" enctype="multipart/form-data" class="mt-6 space-y-5">
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
            <label for="bio" class="block text-sm font-medium text-neutral-700">Bio</label>
            <textarea
                id="bio"
                name="bio"
                rows="4"
                maxlength="1000"
                placeholder="Tell other members about yourself..."
                class="mt-1 block w-full rounded-md border border-neutral-200 px-3 py-2 text-sm text-neutral-900 placeholder-neutral-400 focus:border-green-500 focus:ring-1 focus:ring-green-500 focus:outline-none"
            >{{ old('bio', $user->bio) }}</textarea>
            @error('bio')
                <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="location" class="block text-sm font-medium text-neutral-700">Location</label>
            <input
                type="text"
                id="location"
                name="location"
                value="{{ old('location', $user->location) }}"
                placeholder="e.g. New York, NY"
                class="mt-1 block w-full rounded-md border border-neutral-200 px-3 py-2 text-sm text-neutral-900 placeholder-neutral-400 focus:border-green-500 focus:ring-1 focus:ring-green-500 focus:outline-none"
            />
            <p class="mt-1 text-xs text-neutral-400">Your location will be geocoded in the background after saving.</p>
            @error('location')
                <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="timezone" class="block text-sm font-medium text-neutral-700">Timezone</label>
            <select
                id="timezone"
                name="timezone"
                class="mt-1 block w-full rounded-md border border-neutral-200 px-3 py-2 text-sm text-neutral-900 focus:border-green-500 focus:ring-1 focus:ring-green-500 focus:outline-none"
            >
                @foreach (timezone_identifiers_list() as $tz)
                    <option value="{{ $tz }}" @selected(old('timezone', $user->timezone) === $tz)>{{ $tz }}</option>
                @endforeach
            </select>
            @error('timezone')
                <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="avatar" class="block text-sm font-medium text-neutral-700">Avatar</label>
            @if ($user->getFirstMediaUrl('avatar', 'profile-card'))
                <img src="{{ $user->getFirstMediaUrl('avatar', 'profile-card') }}" alt="Current avatar" class="mt-2 h-24 w-24 rounded-full object-cover" />
            @endif
            <input
                type="file"
                id="avatar"
                name="avatar"
                accept="image/jpeg,image/png,image/webp"
                class="mt-2 block w-full text-sm text-neutral-500 file:mr-4 file:rounded-md file:border-0 file:bg-green-50 file:px-4 file:py-2 file:text-sm file:font-medium file:text-green-700 hover:file:bg-green-100"
            />
            <p class="mt-1 text-xs text-neutral-400">JPEG, PNG, or WebP. Max 2MB.</p>
            @error('avatar')
                <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label class="block text-sm font-medium text-neutral-700">Interests</label>
            <div class="mt-2 flex flex-wrap gap-2">
                @foreach ($interestTags ?? [] as $tag)
                    <label class="inline-flex items-center gap-1.5 rounded-pill border border-neutral-200 px-3 py-1.5 text-sm cursor-pointer has-[:checked]:bg-green-50 has-[:checked]:border-green-500 has-[:checked]:text-green-700">
                        <input
                            type="checkbox"
                            name="interests[]"
                            value="{{ $tag }}"
                            class="sr-only"
                            @checked(in_array($tag, old('interests', $user->tagsWithType('interest')->pluck('name')->toArray())))
                        />
                        {{ $tag }}
                    </label>
                @endforeach
            </div>
            @error('interests')
                <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
            @enderror
            @error('interests.*')
                <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label class="block text-sm font-medium text-neutral-700">Looking for</label>
            <div class="mt-2 space-y-2">
                @php
                    $lookingForOptions = \App\Http\Requests\Settings\UpdateProfileRequest::LOOKING_FOR_OPTIONS;
                    $currentLookingFor = old('looking_for', $user->looking_for ?? []);
                @endphp
                @foreach ($lookingForOptions as $option)
                    <label class="flex items-center gap-2 text-sm text-neutral-700 cursor-pointer">
                        <input
                            type="checkbox"
                            name="looking_for[]"
                            value="{{ $option }}"
                            class="rounded border-neutral-200 text-green-500 focus:ring-green-500"
                            @checked(in_array($option, $currentLookingFor))
                        />
                        {{ ucfirst($option) }}
                    </label>
                @endforeach
            </div>
            @error('looking_for')
                <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
            @enderror
            @error('looking_for.*')
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
