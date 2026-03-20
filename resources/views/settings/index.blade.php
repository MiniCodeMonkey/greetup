<x-layouts.app title="Settings" description="Manage your account settings and preferences.">
    <div class="mx-auto max-w-3xl px-4 py-10">
        <h1 class="text-2xl font-medium text-neutral-900">Settings</h1>

        <div class="mt-6">
            <x-tab-bar :tabs="[
                ['label' => 'Profile', 'href' => route('settings', ['section' => 'profile']), 'active' => $section === 'profile'],
                ['label' => 'Account', 'href' => route('settings', ['section' => 'account']), 'active' => $section === 'account'],
                ['label' => 'Notifications', 'href' => route('settings', ['section' => 'notifications']), 'active' => $section === 'notifications'],
                ['label' => 'Privacy', 'href' => route('settings', ['section' => 'privacy']), 'active' => $section === 'privacy'],
            ]" />
        </div>

        @if (session('status'))
            <div class="mt-6 rounded-md bg-green-50 px-4 py-3 text-sm text-green-700">
                {{ session('status') }}
            </div>
        @endif

        <div class="mt-8">
            @if ($section === 'profile')
                @include('settings.partials.profile')
            @elseif ($section === 'account')
                @include('settings.partials.account')
            @elseif ($section === 'notifications')
                @include('settings.partials.notifications')
            @elseif ($section === 'privacy')
                @include('settings.partials.privacy')
            @endif
        </div>
    </div>
</x-layouts.app>
