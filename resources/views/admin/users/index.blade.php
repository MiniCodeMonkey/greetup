<x-layouts.app :title="$seoTitle">
    <div class="mx-auto max-w-6xl px-4 py-8 sm:px-6">
        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-medium text-neutral-900">Manage Users</h1>
            <a href="{{ route('admin.dashboard') }}" class="text-sm text-green-500 hover:text-green-700">&larr; Back to Dashboard</a>
        </div>

        {{-- Search & Filter --}}
        <form method="GET" action="{{ route('admin.users.index') }}" class="mt-6 flex flex-wrap gap-3">
            <input
                type="text"
                name="search"
                value="{{ request('search') }}"
                placeholder="Search by name or email..."
                class="w-full rounded-lg border px-4 py-2 text-sm text-neutral-700 focus:outline-none focus:ring-2 focus:ring-green-500 sm:w-80"
                style="border-color: var(--color-neutral-300)"
            >
            <label class="inline-flex items-center gap-2 text-sm text-neutral-600">
                <input type="checkbox" name="suspended" value="1" {{ request('suspended') === '1' ? 'checked' : '' }} class="rounded">
                Suspended only
            </label>
            <button type="submit" class="rounded-lg bg-green-500 px-4 py-2 text-sm font-medium text-white hover:bg-green-600">
                Search
            </button>
            @if (request('search') || request('suspended'))
                <a href="{{ route('admin.users.index') }}" class="inline-flex items-center rounded-lg px-4 py-2 text-sm text-neutral-500 hover:text-neutral-700">
                    Clear
                </a>
            @endif
        </form>

        {{-- Users Table --}}
        <div class="mt-6 overflow-x-auto rounded-lg bg-white shadow-sm" style="border: 0.5px solid var(--color-neutral-200)">
            <table class="min-w-full divide-y divide-neutral-200">
                <thead class="bg-neutral-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-neutral-500">Name</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-neutral-500">Email</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-neutral-500">Groups</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-neutral-500">RSVPs</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-neutral-500">Status</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-neutral-500">Joined</th>
                        <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-neutral-500">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-neutral-200">
                    @forelse ($users as $user)
                        <tr>
                            <td class="whitespace-nowrap px-4 py-3 text-sm font-medium text-neutral-900">
                                <a href="{{ route('admin.users.show', $user) }}" class="text-green-500 hover:text-green-700">{{ $user->name }}</a>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-sm text-neutral-600">{{ $user->email }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-sm text-neutral-600">{{ $user->groups_count }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-sm text-neutral-600">{{ $user->rsvps_count }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-sm">
                                @if ($user->is_suspended)
                                    <span class="inline-flex items-center rounded-full bg-red-50 px-2 py-0.5 text-xs font-medium text-red-900">Suspended</span>
                                @else
                                    <span class="inline-flex items-center rounded-full bg-green-50 px-2 py-0.5 text-xs font-medium text-green-900">Active</span>
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-sm text-neutral-500">{{ $user->created_at->format('M j, Y') }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-right text-sm">
                                <a href="{{ route('admin.users.show', $user) }}" class="text-green-500 hover:text-green-700">View</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-8 text-center text-sm text-neutral-500">No users found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        <div class="mt-4">
            {{ $users->links() }}
        </div>
    </div>
</x-layouts.app>
