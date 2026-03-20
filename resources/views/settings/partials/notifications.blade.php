<div>
    <h2 class="text-lg font-medium text-neutral-900">Notification Preferences</h2>
    <p class="mt-1 text-sm text-neutral-500">Manage how you receive notifications. Toggle email and web notifications for each type.</p>

    <form method="POST" action="{{ route('settings.notifications.update') }}" class="mt-6">
        @csrf
        @method('PUT')

        @php
            $categories = collect($notificationPreferences)->groupBy('category');
        @endphp

        <div class="space-y-8">
            @foreach ($categories as $category => $types)
                <div>
                    <h3 class="text-sm font-medium text-neutral-900 uppercase tracking-wide">{{ $category }}</h3>

                    <div class="mt-3 divide-y divide-neutral-100">
                        @foreach ($types as $type => $pref)
                            <div class="flex items-center justify-between py-3">
                                <span class="text-sm text-neutral-700">{{ $pref['label'] }}</span>

                                <div class="flex items-center gap-4">
                                    @if ($pref['web'] !== null)
                                        <label class="flex items-center gap-1.5 cursor-pointer">
                                            <input type="hidden" name="preferences[{{ $type }}][web]" value="0" />
                                            <input
                                                type="checkbox"
                                                name="preferences[{{ $type }}][web]"
                                                value="1"
                                                @checked(old("preferences.{$type}.web", $pref['web']))
                                                class="h-4 w-4 rounded border-neutral-400 text-green-500 focus:ring-green-500"
                                            />
                                            <span class="text-xs text-neutral-500">Web</span>
                                        </label>
                                    @endif

                                    @if ($pref['email'] !== null)
                                        <label class="flex items-center gap-1.5 cursor-pointer">
                                            <input type="hidden" name="preferences[{{ $type }}][email]" value="0" />
                                            <input
                                                type="checkbox"
                                                name="preferences[{{ $type }}][email]"
                                                value="1"
                                                @checked(old("preferences.{$type}.email", $pref['email']))
                                                class="h-4 w-4 rounded border-neutral-400 text-green-500 focus:ring-green-500"
                                            />
                                            <span class="text-xs text-neutral-500">Email</span>
                                        </label>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>

        @error('preferences')
            <p class="mt-2 text-sm text-red-500">{{ $message }}</p>
        @enderror

        <div class="mt-6">
            <button type="submit" class="rounded-md bg-green-500 px-4 py-2.5 text-sm font-medium text-white hover:bg-green-700 focus:ring-2 focus:ring-green-500 focus:ring-offset-2 focus:outline-none">
                Save notification preferences
            </button>
        </div>
    </form>
</div>
