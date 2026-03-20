<x-layouts.app :title="$seoTitle">
    <div class="mx-auto max-w-6xl px-4 py-8 sm:px-6">
        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-medium text-neutral-900">Manage Groups</h1>
            <a href="{{ route('admin.dashboard') }}" class="text-sm text-green-500 hover:text-green-700">&larr; Back to Dashboard</a>
        </div>

        {{-- Search & Filter --}}
        <form method="GET" action="{{ route('admin.groups.index') }}" class="mt-6 flex flex-wrap gap-3">
            <input
                type="text"
                name="search"
                value="{{ request('search') }}"
                placeholder="Search by name or location..."
                class="w-full rounded-lg border px-4 py-2 text-sm text-neutral-700 focus:outline-none focus:ring-2 focus:ring-green-500 sm:w-80"
                style="border-color: var(--color-neutral-300)"
            >
            <select
                name="visibility"
                class="rounded-lg border px-4 py-2 text-sm text-neutral-700 focus:outline-none focus:ring-2 focus:ring-green-500"
                style="border-color: var(--color-neutral-300)"
            >
                <option value="">All Visibility</option>
                <option value="public" {{ request('visibility') === 'public' ? 'selected' : '' }}>Public</option>
                <option value="private" {{ request('visibility') === 'private' ? 'selected' : '' }}>Private</option>
            </select>
            <button type="submit" class="rounded-lg bg-green-500 px-4 py-2 text-sm font-medium text-white hover:bg-green-600">
                Search
            </button>
            @if (request('search') || request('visibility'))
                <a href="{{ route('admin.groups.index') }}" class="inline-flex items-center rounded-lg px-4 py-2 text-sm text-neutral-500 hover:text-neutral-700">
                    Clear
                </a>
            @endif
        </form>

        {{-- Groups Table --}}
        <div class="mt-6 overflow-x-auto rounded-lg bg-white shadow-sm" style="border: 0.5px solid var(--color-neutral-200)">
            <table class="min-w-full divide-y divide-neutral-200">
                <thead class="bg-neutral-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-neutral-500">Name</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-neutral-500">Organizer</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-neutral-500">Members</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-neutral-500">Events</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-neutral-500">Visibility</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-neutral-500">Created</th>
                        <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-neutral-500">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-neutral-200">
                    @forelse ($groups as $group)
                        <tr>
                            <td class="whitespace-nowrap px-4 py-3 text-sm font-medium text-neutral-900">
                                <a href="{{ route('admin.groups.show', $group) }}" class="text-green-500 hover:text-green-700">{{ $group->name }}</a>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-sm text-neutral-600">{{ $group->organizer?->name ?? '—' }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-sm text-neutral-600">{{ $group->members_count }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-sm text-neutral-600">{{ $group->events_count }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-sm">
                                <span class="inline-flex items-center rounded-full bg-neutral-50 px-2 py-0.5 text-xs font-medium text-neutral-700">{{ $group->visibility instanceof \App\Enums\GroupVisibility ? $group->visibility->value : $group->visibility }}</span>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-sm text-neutral-500">{{ $group->created_at->format('M j, Y') }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-right text-sm">
                                <a href="{{ route('admin.groups.show', $group) }}" class="text-green-500 hover:text-green-700">View</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-8 text-center text-sm text-neutral-500">No groups found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        <div class="mt-4">
            {{ $groups->links() }}
        </div>
    </div>
</x-layouts.app>
