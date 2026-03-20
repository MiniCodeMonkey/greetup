<div>
    {{-- Tabs --}}
    <div class="border-b border-neutral-200">
        <nav class="-mb-px flex gap-6">
            @foreach (['going' => 'Going', 'waitlisted' => 'Waitlisted', 'not_going' => 'Not Going'] as $key => $label)
                <button
                    wire:click="setTab('{{ $key }}')"
                    class="whitespace-nowrap border-b-2 px-1 py-3 text-sm font-medium {{ $tab === $key ? 'border-green-500 text-green-600' : 'border-transparent text-neutral-500 hover:border-neutral-300 hover:text-neutral-700' }}"
                >
                    {{ $label }} ({{ $counts[$key] }})
                </button>
            @endforeach
        </nav>
    </div>

    {{-- Table --}}
    <div class="mt-6 overflow-hidden rounded-lg border border-neutral-200">
        <table class="min-w-full divide-y divide-neutral-200">
            <thead class="bg-neutral-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-neutral-500">Name</th>
                    <th class="px-4 py-3 text-center text-xs font-medium uppercase tracking-wider text-neutral-500">Guest Count</th>
                    <th class="px-4 py-3 text-center text-xs font-medium uppercase tracking-wider text-neutral-500">Checked In</th>
                    @if ($isPast)
                        <th class="px-4 py-3 text-center text-xs font-medium uppercase tracking-wider text-neutral-500">Attendance</th>
                    @endif
                    <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-neutral-500">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-neutral-200 bg-white">
                @forelse ($rsvps as $rsvp)
                    <tr wire:key="rsvp-{{ $rsvp->id }}">
                        <td class="whitespace-nowrap px-4 py-3 text-sm font-medium text-neutral-900">{{ $rsvp->user->name }}</td>
                        <td class="whitespace-nowrap px-4 py-3 text-center text-sm text-neutral-500">{{ $rsvp->guest_count }}</td>
                        <td class="whitespace-nowrap px-4 py-3 text-center text-sm">
                            @if ($rsvp->checked_in)
                                <span class="inline-flex items-center rounded-full bg-green-50 px-2 py-1 text-xs font-medium text-green-700">Yes</span>
                            @else
                                <span class="inline-flex items-center rounded-full bg-neutral-100 px-2 py-1 text-xs font-medium text-neutral-500">No</span>
                            @endif
                        </td>
                        @if ($isPast)
                            <td class="whitespace-nowrap px-4 py-3 text-center text-sm">
                                @if ($rsvp->attended === \App\Enums\AttendanceResult::Attended)
                                    <span class="inline-flex items-center rounded-full bg-green-50 px-2 py-1 text-xs font-medium text-green-700">Attended</span>
                                @elseif ($rsvp->attended === \App\Enums\AttendanceResult::NoShow)
                                    <span class="inline-flex items-center rounded-full bg-red-50 px-2 py-1 text-xs font-medium text-red-700">No Show</span>
                                @else
                                    <span class="text-xs text-neutral-400">—</span>
                                @endif
                            </td>
                        @endif
                        <td class="whitespace-nowrap px-4 py-3 text-right text-sm">
                            <div class="flex items-center justify-end gap-2">
                                {{-- Check in (only for Going tab, not already checked in) --}}
                                @if ($tab === 'going' && ! $rsvp->checked_in)
                                    <button wire:click="checkIn({{ $rsvp->id }})" class="text-green-600 hover:text-green-800">Check In</button>
                                @endif

                                {{-- Move waitlisted to going --}}
                                @if ($tab === 'waitlisted')
                                    <button wire:click="moveToGoing({{ $rsvp->id }})" class="text-green-600 hover:text-green-800">Move to Going</button>
                                @endif

                                {{-- Change status --}}
                                @if ($tab !== 'going')
                                    <button wire:click="changeStatus({{ $rsvp->id }}, 'going')" class="text-blue-600 hover:text-blue-800">Set Going</button>
                                @endif
                                @if ($tab !== 'waitlisted')
                                    <button wire:click="changeStatus({{ $rsvp->id }}, 'waitlisted')" class="text-yellow-600 hover:text-yellow-800">Set Waitlisted</button>
                                @endif
                                @if ($tab !== 'not_going')
                                    <button wire:click="changeStatus({{ $rsvp->id }}, 'not_going')" class="text-neutral-600 hover:text-neutral-800">Set Not Going</button>
                                @endif

                                {{-- Mark attendance (past events only) --}}
                                @if ($isPast && $tab === 'going')
                                    <button wire:click="markAttendance({{ $rsvp->id }}, 'attended')" class="text-green-600 hover:text-green-800">Attended</button>
                                    <button wire:click="markAttendance({{ $rsvp->id }}, 'no_show')" class="text-red-600 hover:text-red-800">No Show</button>
                                @endif

                                {{-- Remove RSVP --}}
                                <button wire:click="removeRsvp({{ $rsvp->id }})" wire:confirm="Remove this RSVP?" class="text-red-500 hover:text-red-700">Remove</button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ $isPast ? 5 : 4 }}" class="px-4 py-8 text-center text-sm text-neutral-500">No attendees in this tab.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $rsvps->links() }}
    </div>
</div>
