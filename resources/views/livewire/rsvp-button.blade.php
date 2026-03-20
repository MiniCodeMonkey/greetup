<div data-testid="rsvp-button-component">
    @if ($errorMessage)
        <div class="mb-2 rounded-md bg-red-50 px-3 py-2 text-sm text-red-700" data-testid="rsvp-error">
            {{ $errorMessage }}
        </div>
    @endif

    @if ($canRsvp)
        @if ($currentStatus === 'going')
            {{-- Currently going - show status and cancel option --}}
            <div class="flex flex-wrap items-center gap-3">
                <span class="inline-flex items-center rounded-md bg-green-50 px-4 py-2 text-sm font-medium text-green-700" data-testid="rsvp-status-going">
                    ✓ Going
                </span>
                <button
                    wire:click="rsvpNotGoing"
                    class="inline-flex items-center rounded-md px-4 py-2 text-sm font-medium text-neutral-700 hover:bg-neutral-100"
                    style="border: 0.5px solid var(--color-neutral-200);"
                    data-testid="rsvp-not-going"
                >
                    Not Going
                </button>
            </div>
        @elseif ($currentStatus === 'waitlisted')
            {{-- Currently waitlisted --}}
            <div class="flex flex-wrap items-center gap-3">
                <span class="inline-flex items-center rounded-md bg-gold-50 px-4 py-2 text-sm font-medium text-gold-900" data-testid="rsvp-status-waitlisted">
                    Waitlisted
                </span>
                <button
                    wire:click="rsvpNotGoing"
                    class="inline-flex items-center rounded-md px-4 py-2 text-sm font-medium text-neutral-700 hover:bg-neutral-100"
                    style="border: 0.5px solid var(--color-neutral-200);"
                    data-testid="rsvp-not-going"
                >
                    Cancel
                </button>
            </div>
        @else
            {{-- No current RSVP or not going - show RSVP form --}}
            <div class="space-y-3">
                @if ($isHybrid)
                    <div>
                        <label class="block text-sm font-medium text-neutral-700">Attendance Mode</label>
                        <select wire:model="attendanceMode" class="mt-1 block w-full rounded-md border border-neutral-200 px-3 py-2 text-sm" data-testid="attendance-mode-select">
                            <option value="">Select...</option>
                            <option value="in_person">In Person</option>
                            <option value="online">Online</option>
                        </select>
                    </div>
                @endif

                @if ($maxGuests > 0)
                    <div>
                        <label class="block text-sm font-medium text-neutral-700">Guests (max {{ $maxGuests }})</label>
                        <input type="number" wire:model="guestCount" min="0" max="{{ $maxGuests }}" class="mt-1 block w-20 rounded-md border border-neutral-200 px-3 py-2 text-sm" data-testid="guest-count-input" />
                    </div>
                @endif

                <div class="flex flex-wrap items-center gap-3">
                    @if ($isFull)
                        <button
                            wire:click="rsvpGoing"
                            class="inline-flex items-center rounded-md bg-gold-500 px-4 py-2 text-sm font-medium text-white hover:bg-gold-900"
                            data-testid="rsvp-join-waitlist"
                        >
                            Join Waitlist
                        </button>
                    @else
                        <button
                            wire:click="rsvpGoing"
                            class="inline-flex items-center rounded-md bg-green-500 px-4 py-2 text-sm font-medium text-white hover:bg-green-700"
                            data-testid="rsvp-going"
                        >
                            Going
                        </button>
                    @endif
                </div>
            </div>
        @endif
    @endif
</div>
