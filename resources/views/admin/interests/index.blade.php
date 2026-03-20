<x-layouts.app :title="$seoTitle">
    <div class="mx-auto max-w-6xl px-4 py-8 sm:px-6">
        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-medium text-neutral-900">Manage Interests</h1>
            <div class="flex items-center gap-3">
                <a href="{{ route('admin.interests.create') }}" class="rounded-lg bg-green-500 px-4 py-2 text-sm font-medium text-white hover:bg-green-600">
                    Create Interest
                </a>
                <a href="{{ route('admin.dashboard') }}" class="text-sm text-green-500 hover:text-green-700">&larr; Back to Dashboard</a>
            </div>
        </div>

        @if (session('success'))
            <div class="mt-4 rounded-lg bg-green-50 px-4 py-3 text-sm text-green-700">
                {{ session('success') }}
            </div>
        @endif

        {{-- Search --}}
        <form method="GET" action="{{ route('admin.interests.index') }}" class="mt-6 flex flex-wrap gap-3">
            <input
                type="text"
                name="search"
                value="{{ request('search') }}"
                placeholder="Search interests..."
                class="w-full rounded-lg border px-4 py-2 text-sm text-neutral-700 focus:outline-none focus:ring-2 focus:ring-green-500 sm:w-80"
                style="border-color: var(--color-neutral-300)"
            >
            <button type="submit" class="rounded-lg bg-green-500 px-4 py-2 text-sm font-medium text-white hover:bg-green-600">
                Search
            </button>
            @if (request('search'))
                <a href="{{ route('admin.interests.index') }}" class="inline-flex items-center rounded-lg px-4 py-2 text-sm text-neutral-500 hover:text-neutral-700">
                    Clear
                </a>
            @endif
        </form>

        {{-- Interests Table --}}
        <div class="mt-6 overflow-x-auto rounded-lg bg-white shadow-sm" style="border: 0.5px solid var(--color-neutral-200)">
            <table class="min-w-full divide-y divide-neutral-200">
                <thead class="bg-neutral-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-neutral-500">Name</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-neutral-500">Usage Count</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-neutral-500">Created</th>
                        <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-neutral-500">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-neutral-200">
                    @forelse ($interests as $interest)
                        <tr>
                            <td class="whitespace-nowrap px-4 py-3 text-sm font-medium text-neutral-900">{{ $interest->name }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-sm text-neutral-600">{{ $interest->usage_count }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-sm text-neutral-500">{{ $interest->created_at->format('M j, Y') }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-right text-sm">
                                <div class="flex items-center justify-end gap-3">
                                    <a href="{{ route('admin.interests.edit', $interest) }}" class="text-green-500 hover:text-green-700">Edit</a>

                                    {{-- Merge Form --}}
                                    <form method="POST" action="{{ route('admin.interests.merge', $interest) }}" class="inline-flex items-center gap-1" onsubmit="return confirm('Are you sure you want to merge this interest?')">
                                        @csrf
                                        <select name="target_id" class="rounded border px-2 py-1 text-xs text-neutral-700" style="border-color: var(--color-neutral-300)" required>
                                            <option value="">Merge into...</option>
                                            @foreach ($interests as $target)
                                                @if ($target->id !== $interest->id)
                                                    <option value="{{ $target->id }}">{{ $target->name }}</option>
                                                @endif
                                            @endforeach
                                        </select>
                                        <button type="submit" class="text-xs text-violet-500 hover:text-violet-700">Merge</button>
                                    </form>

                                    {{-- Delete Form --}}
                                    <form method="POST" action="{{ route('admin.interests.destroy', $interest) }}" onsubmit="return confirm('Are you sure you want to delete this interest?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-500 hover:text-red-900">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-4 py-8 text-center text-sm text-neutral-500">No interests found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-layouts.app>
