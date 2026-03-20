<x-layouts.app title="Analytics — {{ $group->name }}" description="View group analytics and engagement metrics.">
    <div class="mx-auto max-w-4xl px-4 py-10">
        <div class="mb-2">
            <a href="{{ route('groups.show', $group) }}" class="text-sm text-green-600 hover:text-green-700">&larr; Back to {{ $group->name }}</a>
        </div>

        <h1 class="text-2xl font-medium text-neutral-900">Group Analytics</h1>
        <p class="mt-1 text-sm text-neutral-500">Understand member engagement and group activity.</p>

        <div class="mt-8 grid gap-6 sm:grid-cols-2">
            {{-- Average Attendance Rate --}}
            <div class="rounded-lg border border-neutral-200 bg-white p-6">
                <h3 class="text-sm font-medium text-neutral-500">Average Attendance Rate</h3>
                <p class="mt-2 text-3xl font-semibold text-neutral-900">{{ $averageAttendanceRate }}%</p>
            </div>

            {{-- Average Event Rating --}}
            <div class="rounded-lg border border-neutral-200 bg-white p-6">
                <h3 class="text-sm font-medium text-neutral-500">Average Event Rating</h3>
                <p class="mt-2 text-3xl font-semibold text-neutral-900">{{ $averageEventRating }}<span class="text-lg text-neutral-400">/5</span></p>
            </div>
        </div>

        {{-- Member Growth --}}
        <div class="mt-8 rounded-lg border border-neutral-200 bg-white p-6">
            <h2 class="text-lg font-medium text-neutral-900">Member Growth</h2>
            <p class="mt-1 text-sm text-neutral-500">New members per month.</p>

            @if ($memberGrowth->isEmpty())
                <p class="mt-4 text-sm text-neutral-400">No member data available yet.</p>
            @else
                <div class="mt-4 overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead>
                            <tr class="border-b border-neutral-200">
                                <th class="pb-2 font-medium text-neutral-500">Month</th>
                                <th class="pb-2 font-medium text-neutral-500">New Members</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($memberGrowth as $row)
                                <tr class="border-b border-neutral-100">
                                    <td class="py-2 text-neutral-700">{{ $row->period }}</td>
                                    <td class="py-2 text-neutral-900">{{ $row->count }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        {{-- Event Count Over Time --}}
        <div class="mt-6 rounded-lg border border-neutral-200 bg-white p-6">
            <h2 class="text-lg font-medium text-neutral-900">Events Over Time</h2>
            <p class="mt-1 text-sm text-neutral-500">Published and past events per month.</p>

            @if ($eventCountOverTime->isEmpty())
                <p class="mt-4 text-sm text-neutral-400">No event data available yet.</p>
            @else
                <div class="mt-4 overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead>
                            <tr class="border-b border-neutral-200">
                                <th class="pb-2 font-medium text-neutral-500">Month</th>
                                <th class="pb-2 font-medium text-neutral-500">Events</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($eventCountOverTime as $row)
                                <tr class="border-b border-neutral-100">
                                    <td class="py-2 text-neutral-700">{{ $row->period }}</td>
                                    <td class="py-2 text-neutral-900">{{ $row->count }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        {{-- Most Active Members --}}
        <div class="mt-6 rounded-lg border border-neutral-200 bg-white p-6">
            <h2 class="text-lg font-medium text-neutral-900">Most Active Members</h2>
            <p class="mt-1 text-sm text-neutral-500">Top members by attendance count.</p>

            @if ($mostActiveMembers->isEmpty())
                <p class="mt-4 text-sm text-neutral-400">No attendance data available yet.</p>
            @else
                <div class="mt-4 overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead>
                            <tr class="border-b border-neutral-200">
                                <th class="pb-2 font-medium text-neutral-500">Member</th>
                                <th class="pb-2 font-medium text-neutral-500">Events Attended</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($mostActiveMembers as $member)
                                <tr class="border-b border-neutral-100">
                                    <td class="py-2 text-neutral-700">{{ $member->name }}</td>
                                    <td class="py-2 text-neutral-900">{{ $member->attendance_count }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</x-layouts.app>
