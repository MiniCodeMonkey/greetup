<x-layouts.app title="Attendees — {{ $event->name }}" description="Manage event attendees.">
    <div class="mx-auto max-w-4xl px-4 py-10">
        <div class="mb-2">
            <a href="{{ route('events.show', [$group, $event]) }}" class="text-sm text-green-600 hover:text-green-700">&larr; Back to {{ $event->name }}</a>
        </div>

        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-medium text-neutral-900">Attendees</h1>
                <p class="mt-1 text-sm text-neutral-500">Manage attendees for {{ $event->name }}.</p>
            </div>
            <a href="{{ route('events.attendees.export', [$group, $event]) }}" class="inline-flex items-center rounded-md bg-green-500 px-4 py-2 text-sm font-medium text-white hover:bg-green-600">
                Export CSV
            </a>
        </div>

        @if (session('status'))
            <div class="mt-6 rounded-md bg-green-50 px-4 py-3 text-sm text-green-700">
                {{ session('status') }}
            </div>
        @endif

        <div class="mt-6">
            <livewire:attendee-manager :event="$event" />
        </div>
    </div>
</x-layouts.app>
