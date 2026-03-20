<x-layouts.app title="Leadership Team — {{ $group->name }}" description="Manage group leadership team.">
    <div class="mx-auto max-w-4xl px-4 py-10">
        <div class="mb-2">
            <a href="{{ route('groups.show', $group) }}" class="text-sm text-green-600 hover:text-green-700">&larr; Back to {{ $group->name }}</a>
        </div>

        <h1 class="text-2xl font-medium text-neutral-900">Leadership Team</h1>
        <p class="mt-1 text-sm text-neutral-500">Promote or demote members within the leadership team.</p>

        @if (session('status'))
            <div class="mt-6 rounded-md bg-green-50 px-4 py-3 text-sm text-green-700">
                {{ session('status') }}
            </div>
        @endif

        {{-- Current Leadership --}}
        <div class="mt-8">
            <h2 class="text-lg font-medium text-neutral-900">Current Leadership</h2>
            <div class="mt-4 overflow-hidden rounded-lg border border-neutral-200">
                <table class="min-w-full divide-y divide-neutral-200">
                    <thead class="bg-neutral-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-neutral-500">Name</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-neutral-500">Role</th>
                            <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-neutral-500">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-neutral-200 bg-white">
                        @forelse ($leadershipMembers as $member)
                            @php
                                $role = $member->pivot->role instanceof \App\Enums\GroupRole ? $member->pivot->role : \App\Enums\GroupRole::from($member->pivot->role);
                            @endphp
                            <tr>
                                <td class="whitespace-nowrap px-4 py-3 text-sm font-medium text-neutral-900">{{ $member->name }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-sm text-neutral-500">{{ ucfirst(str_replace('_', ' ', $role->value)) }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-right text-sm">
                                    @if ($role !== \App\Enums\GroupRole::Organizer)
                                        @if ($isOrganizer || $role !== \App\Enums\GroupRole::CoOrganizer)
                                            <form method="POST" action="{{ route('groups.manage.team.update-role', [$group, $member]) }}" class="inline-flex items-center gap-2">
                                                @csrf
                                                <select name="role" class="rounded-md border border-neutral-200 px-2 py-1 text-sm text-neutral-700 focus:border-green-500 focus:ring-1 focus:ring-green-500 focus:outline-none">
                                                    <option value="member" @selected($role === \App\Enums\GroupRole::Member)>Member</option>
                                                    <option value="event_organizer" @selected($role === \App\Enums\GroupRole::EventOrganizer)>Event organizer</option>
                                                    <option value="assistant_organizer" @selected($role === \App\Enums\GroupRole::AssistantOrganizer)>Assistant organizer</option>
                                                    @if ($isOrganizer)
                                                        <option value="co_organizer" @selected($role === \App\Enums\GroupRole::CoOrganizer)>Co-organizer</option>
                                                    @endif
                                                </select>
                                                <button type="submit" class="rounded-md bg-green-500 px-3 py-1 text-sm font-medium text-white hover:bg-green-600">
                                                    Update
                                                </button>
                                            </form>
                                        @else
                                            <span class="text-xs text-neutral-400">Only organizer can change</span>
                                        @endif
                                    @else
                                        <span class="text-xs text-neutral-400">Primary organizer</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="px-4 py-8 text-center text-sm text-neutral-500">No leadership members.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Promote Regular Members --}}
        @if ($regularMembers->isNotEmpty())
            <div class="mt-8">
                <h2 class="text-lg font-medium text-neutral-900">Promote Members</h2>
                <p class="mt-1 text-sm text-neutral-500">Promote regular members to leadership roles.</p>
                <div class="mt-4 overflow-hidden rounded-lg border border-neutral-200">
                    <table class="min-w-full divide-y divide-neutral-200">
                        <thead class="bg-neutral-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-neutral-500">Name</th>
                                <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-neutral-500">Promote to</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-neutral-200 bg-white">
                            @foreach ($regularMembers as $member)
                                <tr>
                                    <td class="whitespace-nowrap px-4 py-3 text-sm font-medium text-neutral-900">{{ $member->name }}</td>
                                    <td class="whitespace-nowrap px-4 py-3 text-right text-sm">
                                        <form method="POST" action="{{ route('groups.manage.team.update-role', [$group, $member]) }}" class="inline-flex items-center gap-2">
                                            @csrf
                                            <select name="role" class="rounded-md border border-neutral-200 px-2 py-1 text-sm text-neutral-700 focus:border-green-500 focus:ring-1 focus:ring-green-500 focus:outline-none">
                                                <option value="event_organizer">Event organizer</option>
                                                <option value="assistant_organizer">Assistant organizer</option>
                                                @if ($isOrganizer)
                                                    <option value="co_organizer">Co-organizer</option>
                                                @endif
                                            </select>
                                            <button type="submit" class="rounded-md bg-green-500 px-3 py-1 text-sm font-medium text-white hover:bg-green-600">
                                                Promote
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </div>
</x-layouts.app>
