<x-layouts.app title="Edit Event" description="Edit {{ $event->name }}.">
    <div class="mx-auto max-w-3xl px-4 py-10">
        <h1 class="text-2xl font-medium text-neutral-900">Edit Event</h1>
        <p class="mt-1 text-sm text-neutral-500">Editing <strong>{{ $event->name }}</strong> in {{ $group->name }}.</p>

        @if (session('status'))
            <div class="mt-6 rounded-md bg-green-50 px-4 py-3 text-sm text-green-700">
                {{ session('status') }}
            </div>
        @endif

        <form method="POST" action="{{ route('events.update', [$group, $event]) }}" class="mt-8 space-y-6">
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

            {{-- Venue --}}
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
                    @error('venue_address')
                        <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            {{-- Online Link --}}
            <div>
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
</x-layouts.app>
