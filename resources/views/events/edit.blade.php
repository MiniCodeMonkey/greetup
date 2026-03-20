<x-layouts.app title="Edit Event" description="Edit {{ $event->name }}.">
    <div class="mx-auto max-w-3xl px-4 py-10">
        <h1 class="text-2xl font-medium text-neutral-900">Edit Event</h1>
        <p class="mt-1 text-sm text-neutral-500">Editing <strong>{{ $event->name }}</strong> in {{ $group->name }}.</p>

        @if (session('status'))
            <div class="mt-6 rounded-md bg-green-50 px-4 py-3 text-sm text-green-700">
                {{ session('status') }}
            </div>
        @endif

        <form method="POST" action="{{ route('events.update', [$group, $event]) }}" enctype="multipart/form-data" class="mt-8 space-y-6">
            @csrf
            @method('PUT')

            @if ($event->series_id)
                <div class="rounded-md border border-violet-200 bg-violet-50 px-4 py-3">
                    <p class="text-sm font-medium text-violet-700">This event is part of a recurring series.</p>
                    <div class="mt-2 space-y-2">
                        <label class="flex items-center gap-2">
                            <input type="radio" name="edit_scope" value="single" checked class="text-violet-500 focus:ring-violet-500" />
                            <span class="text-sm text-neutral-700">Edit this event only</span>
                        </label>
                        <label class="flex items-center gap-2">
                            <input type="radio" name="edit_scope" value="all_future" class="text-violet-500 focus:ring-violet-500" />
                            <span class="text-sm text-neutral-700">Edit this and all future events</span>
                        </label>
                    </div>
                </div>
            @endif

            {{-- Name --}}
            <div>
                <label for="name" class="block text-sm font-medium text-neutral-700">Event Name <span class="text-red-500">*</span></label>
                <input
                    type="text"
                    id="name"
                    name="name"
                    value="{{ old('name', $event->name) }}"
                    required
                    class="mt-1 block w-full rounded-md border border-neutral-200 px-3 py-2 text-sm text-neutral-900 placeholder-neutral-400 focus:border-green-500 focus:ring-1 focus:ring-green-500 focus:outline-none"
                />
                @error('name')
                    <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                @enderror
            </div>

            {{-- Description --}}
            <div>
                <label for="description" class="block text-sm font-medium text-neutral-700">Description</label>
                <p class="mt-0.5 text-xs text-neutral-400">Supports Markdown formatting.</p>
                <textarea
                    id="description"
                    name="description"
                    rows="6"
                    maxlength="10000"
                    class="mt-1 block w-full rounded-md border border-neutral-200 px-3 py-2 text-sm text-neutral-900 placeholder-neutral-400 focus:border-green-500 focus:ring-1 focus:ring-green-500 focus:outline-none font-mono"
                >{{ old('description', $event->description) }}</textarea>
                @error('description')
                    <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                @enderror
            </div>

            {{-- Event Type --}}
            <div>
                <label class="block text-sm font-medium text-neutral-700">Event Type <span class="text-red-500">*</span></label>
                <div class="mt-2 space-y-2">
                    @php $currentType = old('event_type', $event->event_type->value); @endphp
                    <label class="flex items-center gap-2">
                        <input type="radio" name="event_type" value="in_person" {{ $currentType === 'in_person' ? 'checked' : '' }} class="text-green-500 focus:ring-green-500" />
                        <span class="text-sm text-neutral-700">In Person</span>
                    </label>
                    <label class="flex items-center gap-2">
                        <input type="radio" name="event_type" value="online" {{ $currentType === 'online' ? 'checked' : '' }} class="text-green-500 focus:ring-green-500" />
                        <span class="text-sm text-neutral-700">Online</span>
                    </label>
                    <label class="flex items-center gap-2">
                        <input type="radio" name="event_type" value="hybrid" {{ $currentType === 'hybrid' ? 'checked' : '' }} class="text-green-500 focus:ring-green-500" />
                        <span class="text-sm text-neutral-700">Hybrid</span>
                    </label>
                </div>
                @error('event_type')
                    <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                @enderror
            </div>

            {{-- Start Date/Time --}}
            @php
                $tz = $event->timezone ?? $group->timezone ?? config('app.timezone');
                $startsLocal = $event->starts_at ? $event->starts_at->setTimezone($tz)->format('Y-m-d\TH:i') : '';
                $endsLocal = $event->ends_at ? $event->ends_at->setTimezone($tz)->format('Y-m-d\TH:i') : '';
            @endphp
            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                <div>
                    <label for="starts_at" class="block text-sm font-medium text-neutral-700">Starts At <span class="text-red-500">*</span></label>
                    <input
                        type="datetime-local"
                        id="starts_at"
                        name="starts_at"
                        value="{{ old('starts_at', $startsLocal) }}"
                        required
                        class="mt-1 block w-full rounded-md border border-neutral-200 px-3 py-2 text-sm text-neutral-900 focus:border-green-500 focus:ring-1 focus:ring-green-500 focus:outline-none"
                    />
                    @error('starts_at')
                        <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="ends_at" class="block text-sm font-medium text-neutral-700">Ends At</label>
                    <input
                        type="datetime-local"
                        id="ends_at"
                        name="ends_at"
                        value="{{ old('ends_at', $endsLocal) }}"
                        class="mt-1 block w-full rounded-md border border-neutral-200 px-3 py-2 text-sm text-neutral-900 focus:border-green-500 focus:ring-1 focus:ring-green-500 focus:outline-none"
                    />
                    @error('ends_at')
                        <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            {{-- Timezone --}}
            <div>
                <label for="timezone" class="block text-sm font-medium text-neutral-700">Timezone</label>
                <input
                    type="text"
                    id="timezone"
                    name="timezone"
                    value="{{ old('timezone', $tz) }}"
                    placeholder="e.g. America/New_York"
                    class="mt-1 block w-full rounded-md border border-neutral-200 px-3 py-2 text-sm text-neutral-900 placeholder-neutral-400 focus:border-green-500 focus:ring-1 focus:ring-green-500 focus:outline-none"
                />
                @error('timezone')
                    <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                @enderror
            </div>

            {{-- Venue (for in_person/hybrid) --}}
            <div id="venue_fields">
                <div class="space-y-4">
                    <div>
                        <label for="venue_name" class="block text-sm font-medium text-neutral-700">Venue Name</label>
                        <input
                            type="text"
                            id="venue_name"
                            name="venue_name"
                            value="{{ old('venue_name', $event->venue_name) }}"
                            class="mt-1 block w-full rounded-md border border-neutral-200 px-3 py-2 text-sm text-neutral-900 placeholder-neutral-400 focus:border-green-500 focus:ring-1 focus:ring-green-500 focus:outline-none"
                        />
                        @error('venue_name')
                            <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="venue_address" class="block text-sm font-medium text-neutral-700">Venue Address</label>
                        <input
                            type="text"
                            id="venue_address"
                            name="venue_address"
                            value="{{ old('venue_address', $event->venue_address) }}"
                            class="mt-1 block w-full rounded-md border border-neutral-200 px-3 py-2 text-sm text-neutral-900 placeholder-neutral-400 focus:border-green-500 focus:ring-1 focus:ring-green-500 focus:outline-none"
                        />
                        <p class="mt-1 text-xs text-neutral-400">The address will be geocoded in the background after saving.</p>
                        @error('venue_address')
                            <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            {{-- Online Link (for online/hybrid) --}}
            <div id="online_link_field">
                <label for="online_link" class="block text-sm font-medium text-neutral-700">Online Link</label>
                <input
                    type="url"
                    id="online_link"
                    name="online_link"
                    value="{{ old('online_link', $event->online_link) }}"
                    class="mt-1 block w-full rounded-md border border-neutral-200 px-3 py-2 text-sm text-neutral-900 placeholder-neutral-400 focus:border-green-500 focus:ring-1 focus:ring-green-500 focus:outline-none"
                />
                @error('online_link')
                    <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                @enderror
            </div>

            {{-- Cover Photo --}}
            <div>
                <label for="cover_photo" class="block text-sm font-medium text-neutral-700">Cover Photo</label>
                @if ($event->getFirstMediaUrl('cover_photo'))
                    <p class="mt-1 text-xs text-neutral-400">A cover photo is already uploaded. Upload a new one to replace it.</p>
                @endif
                <input
                    type="file"
                    id="cover_photo"
                    name="cover_photo"
                    accept="image/jpeg,image/png,image/webp"
                    class="mt-1 block w-full text-sm text-neutral-500 file:mr-4 file:rounded-md file:border-0 file:bg-green-50 file:px-4 file:py-2 file:text-sm file:font-medium file:text-green-700 hover:file:bg-green-100"
                />
                <p class="mt-1 text-xs text-neutral-400">JPEG, PNG, or WebP. Max 5MB.</p>
                @error('cover_photo')
                    <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                @enderror
            </div>

            {{-- RSVP Settings --}}
            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                <div>
                    <label for="rsvp_limit" class="block text-sm font-medium text-neutral-700">RSVP Limit</label>
                    <input
                        type="number"
                        id="rsvp_limit"
                        name="rsvp_limit"
                        value="{{ old('rsvp_limit', $event->rsvp_limit) }}"
                        min="1"
                        placeholder="Leave blank for unlimited"
                        class="mt-1 block w-full rounded-md border border-neutral-200 px-3 py-2 text-sm text-neutral-900 placeholder-neutral-400 focus:border-green-500 focus:ring-1 focus:ring-green-500 focus:outline-none"
                    />
                    @error('rsvp_limit')
                        <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="guest_limit" class="block text-sm font-medium text-neutral-700">Guest Limit</label>
                    <input
                        type="number"
                        id="guest_limit"
                        name="guest_limit"
                        value="{{ old('guest_limit', $event->guest_limit) }}"
                        min="0"
                        max="10"
                        class="mt-1 block w-full rounded-md border border-neutral-200 px-3 py-2 text-sm text-neutral-900 placeholder-neutral-400 focus:border-green-500 focus:ring-1 focus:ring-green-500 focus:outline-none"
                    />
                    @error('guest_limit')
                        <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            @php
                $rsvpOpensLocal = $event->rsvp_opens_at ? $event->rsvp_opens_at->setTimezone($tz)->format('Y-m-d\TH:i') : '';
                $rsvpClosesLocal = $event->rsvp_closes_at ? $event->rsvp_closes_at->setTimezone($tz)->format('Y-m-d\TH:i') : '';
            @endphp
            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                <div>
                    <label for="rsvp_opens_at" class="block text-sm font-medium text-neutral-700">RSVP Opens At</label>
                    <input
                        type="datetime-local"
                        id="rsvp_opens_at"
                        name="rsvp_opens_at"
                        value="{{ old('rsvp_opens_at', $rsvpOpensLocal) }}"
                        class="mt-1 block w-full rounded-md border border-neutral-200 px-3 py-2 text-sm text-neutral-900 focus:border-green-500 focus:ring-1 focus:ring-green-500 focus:outline-none"
                    />
                    @error('rsvp_opens_at')
                        <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="rsvp_closes_at" class="block text-sm font-medium text-neutral-700">RSVP Closes At</label>
                    <input
                        type="datetime-local"
                        id="rsvp_closes_at"
                        name="rsvp_closes_at"
                        value="{{ old('rsvp_closes_at', $rsvpClosesLocal) }}"
                        class="mt-1 block w-full rounded-md border border-neutral-200 px-3 py-2 text-sm text-neutral-900 focus:border-green-500 focus:ring-1 focus:ring-green-500 focus:outline-none"
                    />
                    @error('rsvp_closes_at')
                        <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            {{-- Chat & Comments --}}
            <div class="space-y-3">
                <label class="flex items-center gap-2">
                    <input type="hidden" name="is_chat_enabled" value="0" />
                    <input type="checkbox" name="is_chat_enabled" value="1" {{ old('is_chat_enabled', $event->is_chat_enabled) ? 'checked' : '' }} class="rounded border-neutral-200 text-green-500 focus:ring-green-500" />
                    <span class="text-sm text-neutral-700">Enable event chat</span>
                </label>
                <label class="flex items-center gap-2">
                    <input type="hidden" name="is_comments_enabled" value="0" />
                    <input type="checkbox" name="is_comments_enabled" value="1" {{ old('is_comments_enabled', $event->is_comments_enabled) ? 'checked' : '' }} class="rounded border-neutral-200 text-green-500 focus:ring-green-500" />
                    <span class="text-sm text-neutral-700">Enable comments</span>
                </label>
            </div>

            {{-- Submit --}}
            <div class="flex items-center gap-4 border-t border-neutral-100 pt-6">
                <button type="submit" class="rounded-md bg-green-500 px-6 py-2.5 text-sm font-medium text-white hover:bg-green-700 focus:ring-2 focus:ring-green-500 focus:ring-offset-2 focus:outline-none">
                    Save Changes
                </button>
                <a href="{{ route('groups.show', $group) }}" class="text-sm text-neutral-500 hover:text-neutral-700">Cancel</a>
            </div>
        </form>

        {{-- Cancel Event Section --}}
        <div class="mt-10 border-t border-neutral-100 pt-6">
            <h2 class="text-lg font-medium text-red-500">Cancel Event</h2>
            <form method="POST" action="{{ route('events.cancel', [$group, $event]) }}" class="mt-4 space-y-4">
                @csrf

                @if ($event->series_id)
                    <div class="space-y-2">
                        <label class="flex items-center gap-2">
                            <input type="radio" name="cancel_scope" value="single" checked class="text-red-500 focus:ring-red-500" />
                            <span class="text-sm text-neutral-700">Cancel this event only</span>
                        </label>
                        <label class="flex items-center gap-2">
                            <input type="radio" name="cancel_scope" value="all_future" class="text-red-500 focus:ring-red-500" />
                            <span class="text-sm text-neutral-700">Cancel this and all future events</span>
                        </label>
                    </div>
                @endif

                <div>
                    <label for="cancellation_reason" class="block text-sm font-medium text-neutral-700">Reason (optional)</label>
                    <input
                        type="text"
                        id="cancellation_reason"
                        name="cancellation_reason"
                        placeholder="Why is this event being cancelled?"
                        class="mt-1 block w-full rounded-md border border-neutral-200 px-3 py-2 text-sm text-neutral-900 placeholder-neutral-400 focus:border-red-500 focus:ring-1 focus:ring-red-500 focus:outline-none"
                    />
                </div>

                <button type="submit" class="rounded-md bg-red-500 px-6 py-2.5 text-sm font-medium text-white hover:bg-red-900 focus:ring-2 focus:ring-red-500 focus:ring-offset-2 focus:outline-none" onclick="return confirm('Are you sure you want to cancel this event?')">
                    Cancel Event
                </button>
            </form>
        </div>
    </div>

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const eventTypeRadios = document.querySelectorAll('input[name="event_type"]');
            const venueFields = document.getElementById('venue_fields');
            const onlineLinkField = document.getElementById('online_link_field');

            function toggleConditionalFields() {
                const selected = document.querySelector('input[name="event_type"]:checked');
                if (!selected) return;

                const type = selected.value;
                venueFields.style.display = (type === 'in_person' || type === 'hybrid') ? 'block' : 'none';
                onlineLinkField.style.display = (type === 'online' || type === 'hybrid') ? 'block' : 'none';
            }

            eventTypeRadios.forEach(function (radio) {
                radio.addEventListener('change', toggleConditionalFields);
            });

            toggleConditionalFields();
        });
    </script>
    @endpush
</x-layouts.app>
