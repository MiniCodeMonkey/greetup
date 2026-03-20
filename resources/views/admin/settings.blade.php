<x-layouts.app :title="$seoTitle">
    <div class="mx-auto max-w-2xl px-4 py-8 sm:px-6">
        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-medium text-neutral-900">Platform Settings</h1>
            <a href="{{ route('admin.dashboard') }}" class="text-sm text-green-500 hover:text-green-700">&larr; Back to Dashboard</a>
        </div>

        @if (session('success'))
            <div class="mt-4 rounded-lg bg-green-50 p-4 text-sm text-green-700">
                {{ session('success') }}
            </div>
        @endif

        <div class="mt-6 rounded-lg bg-white p-6 shadow-sm" style="border: 0.5px solid var(--color-neutral-200)">
            <form method="POST" action="{{ route('admin.settings.update') }}">
                @csrf
                @method('PUT')

                {{-- Site Name --}}
                <div>
                    <label for="site_name" class="block text-sm font-medium text-neutral-700">Site Name</label>
                    <input
                        type="text"
                        name="site_name"
                        id="site_name"
                        value="{{ old('site_name', $settings['site_name'] ?? '') }}"
                        class="mt-1 w-full rounded-lg border px-4 py-2 text-sm text-neutral-700 focus:outline-none focus:ring-2 focus:ring-green-500"
                        style="border-color: var(--color-neutral-300)"
                        required
                    >
                    @error('site_name')
                        <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Site Description --}}
                <div class="mt-4">
                    <label for="site_description" class="block text-sm font-medium text-neutral-700">Site Description</label>
                    <input
                        type="text"
                        name="site_description"
                        id="site_description"
                        value="{{ old('site_description', $settings['site_description'] ?? '') }}"
                        class="mt-1 w-full rounded-lg border px-4 py-2 text-sm text-neutral-700 focus:outline-none focus:ring-2 focus:ring-green-500"
                        style="border-color: var(--color-neutral-300)"
                        placeholder="A tagline for your platform"
                    >
                    @error('site_description')
                        <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Registration Enabled --}}
                <div class="mt-4 flex items-center gap-3">
                    <input
                        type="hidden"
                        name="registration_enabled"
                        value="0"
                    >
                    <input
                        type="checkbox"
                        name="registration_enabled"
                        id="registration_enabled"
                        value="1"
                        class="h-4 w-4 rounded border-neutral-300 text-green-500 focus:ring-green-500"
                        {{ old('registration_enabled', $settings['registration_enabled'] ?? '1') == '1' ? 'checked' : '' }}
                    >
                    <label for="registration_enabled" class="text-sm font-medium text-neutral-700">Registration Enabled</label>
                </div>

                {{-- Require Email Verification --}}
                <div class="mt-4 flex items-center gap-3">
                    <input
                        type="hidden"
                        name="require_email_verification"
                        value="0"
                    >
                    <input
                        type="checkbox"
                        name="require_email_verification"
                        id="require_email_verification"
                        value="1"
                        class="h-4 w-4 rounded border-neutral-300 text-green-500 focus:ring-green-500"
                        {{ old('require_email_verification', $settings['require_email_verification'] ?? '1') == '1' ? 'checked' : '' }}
                    >
                    <label for="require_email_verification" class="text-sm font-medium text-neutral-700">Require Email Verification</label>
                </div>

                {{-- Max Groups Per User --}}
                <div class="mt-4">
                    <label for="max_groups_per_user" class="block text-sm font-medium text-neutral-700">Max Groups Per User</label>
                    <input
                        type="number"
                        name="max_groups_per_user"
                        id="max_groups_per_user"
                        value="{{ old('max_groups_per_user', $settings['max_groups_per_user'] ?? '') }}"
                        class="mt-1 w-full rounded-lg border px-4 py-2 text-sm text-neutral-700 focus:outline-none focus:ring-2 focus:ring-green-500"
                        style="border-color: var(--color-neutral-300)"
                        placeholder="Leave empty for unlimited"
                        min="1"
                    >
                    @error('max_groups_per_user')
                        <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Default Timezone --}}
                <div class="mt-4">
                    <label for="default_timezone" class="block text-sm font-medium text-neutral-700">Default Timezone</label>
                    <select
                        name="default_timezone"
                        id="default_timezone"
                        class="mt-1 w-full rounded-lg border px-4 py-2 text-sm text-neutral-700 focus:outline-none focus:ring-2 focus:ring-green-500"
                        style="border-color: var(--color-neutral-300)"
                        required
                    >
                        @foreach (timezone_identifiers_list() as $tz)
                            <option value="{{ $tz }}" {{ old('default_timezone', $settings['default_timezone'] ?? 'UTC') === $tz ? 'selected' : '' }}>
                                {{ $tz }}
                            </option>
                        @endforeach
                    </select>
                    @error('default_timezone')
                        <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Default Locale --}}
                <div class="mt-4">
                    <label for="default_locale" class="block text-sm font-medium text-neutral-700">Default Locale</label>
                    <input
                        type="text"
                        name="default_locale"
                        id="default_locale"
                        value="{{ old('default_locale', $settings['default_locale'] ?? 'en') }}"
                        class="mt-1 w-full rounded-lg border px-4 py-2 text-sm text-neutral-700 focus:outline-none focus:ring-2 focus:ring-green-500"
                        style="border-color: var(--color-neutral-300)"
                        required
                    >
                    @error('default_locale')
                        <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <div class="mt-6">
                    <button type="submit" class="rounded-lg bg-green-500 px-4 py-2 text-sm font-medium text-white hover:bg-green-600">
                        Save Settings
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-layouts.app>
