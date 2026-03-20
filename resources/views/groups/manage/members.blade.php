<x-layouts.app title="Members — {{ $group->name }}" description="Manage group members.">
    <div class="mx-auto max-w-4xl px-4 py-10">
        <div class="mb-2">
            <a href="{{ route('groups.show', $group) }}" class="text-sm text-green-600 hover:text-green-700">&larr; Back to {{ $group->name }}</a>
        </div>

        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-medium text-neutral-900">Members</h1>
                <p class="mt-1 text-sm text-neutral-500">Manage your group's members.</p>
            </div>
            <a href="{{ route('groups.manage.members.export', $group) }}" class="inline-flex items-center rounded-md bg-green-500 px-4 py-2 text-sm font-medium text-white hover:bg-green-600">
                Export CSV
            </a>
        </div>

        @if (session('status'))
            <div class="mt-6 rounded-md bg-green-50 px-4 py-3 text-sm text-green-700">
                {{ session('status') }}
            </div>
        @endif

        {{-- Search --}}
        <form method="GET" action="{{ route('groups.manage.members', $group) }}" class="mt-6">
            <div class="flex gap-2">
                <input
                    type="text"
                    name="search"
                    value="{{ $search }}"
                    placeholder="Search members by name..."
                    class="block w-full rounded-md border border-neutral-200 px-3 py-2 text-sm text-neutral-900 placeholder-neutral-400 focus:border-green-500 focus:ring-1 focus:ring-green-500 focus:outline-none"
                />
                <button type="submit" class="rounded-md bg-neutral-100 px-4 py-2 text-sm font-medium text-neutral-700 hover:bg-neutral-200">
                    Search
                </button>
            </div>
        </form>

        {{-- Member list --}}
        <div class="mt-6 overflow-hidden rounded-lg border border-neutral-200">
            <table class="min-w-full divide-y divide-neutral-200">
                <thead class="bg-neutral-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-neutral-500">Name</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-neutral-500">Role</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-neutral-500">Joined</th>
                        <th class="px-4 py-3 text-center text-xs font-medium uppercase tracking-wider text-neutral-500">Attended</th>
                        <th class="px-4 py-3 text-center text-xs font-medium uppercase tracking-wider text-neutral-500">No-Shows</th>
                        <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-neutral-500">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-neutral-200 bg-white">
                    @forelse ($members as $member)
                        @php
                            $role = $member->pivot->role instanceof \App\Enums\GroupRole ? $member->pivot->role : \App\Enums\GroupRole::from($member->pivot->role);
                            $stats = $memberStats[$member->id] ?? ['attended' => 0, 'no_shows' => 0];
                        @endphp
                        <tr>
                            <td class="whitespace-nowrap px-4 py-3 text-sm font-medium text-neutral-900">{{ $member->name }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-sm text-neutral-500">{{ ucfirst(str_replace('_', ' ', $role->value)) }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-sm text-neutral-500">{{ $member->pivot->joined_at?->format('M j, Y') ?? '—' }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-center text-sm text-neutral-500">{{ $stats['attended'] }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-center text-sm text-neutral-500">{{ $stats['no_shows'] }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-right text-sm">
                                @if ($role !== \App\Enums\GroupRole::Organizer)
                                    <div class="flex items-center justify-end gap-2">
                                        <form method="POST" action="{{ route('groups.manage.members.remove', [$group, $member]) }}" onsubmit="return confirm('Remove {{ $member->name }} from the group?')">
                                            @csrf
                                            <button type="submit" class="text-red-500 hover:text-red-700">Remove</button>
                                        </form>
                                        <form method="POST" action="{{ route('groups.manage.members.ban', [$group, $member]) }}" onsubmit="return confirm('Ban {{ $member->name }} from the group?')">
                                            @csrf
                                            <input type="hidden" name="reason" value="Banned by organizer" />
                                            <button type="submit" class="text-red-500 hover:text-red-700">Ban</button>
                                        </form>
                                    </div>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-sm text-neutral-500">No members found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $members->links() }}
        </div>
    </div>
</x-layouts.app>
