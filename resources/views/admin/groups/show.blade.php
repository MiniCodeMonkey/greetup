<x-layouts.app :title="$seoTitle">
    <div class="mx-auto max-w-6xl px-4 py-8 sm:px-6">
        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-medium text-neutral-900">{{ $group->name }}</h1>
            <a href="{{ route('admin.groups.index') }}" class="text-sm text-green-500 hover:text-green-700">&larr; Back to Groups</a>
        </div>

        {{-- Flash Messages --}}
        @if (session('success'))
            <div class="mt-4 rounded-lg bg-green-50 p-4 text-sm text-green-800">
                {{ session('success') }}
            </div>
        @endif

        {{-- Group Details --}}
        <div class="mt-6 rounded-lg bg-white p-6 shadow-sm" style="border: 0.5px solid var(--color-neutral-200)">
            <dl class="grid gap-4 sm:grid-cols-2">
                <div>
                    <dt class="text-sm font-medium text-neutral-500">Organizer</dt>
                    <dd class="mt-1 text-sm text-neutral-900">{{ $group->organizer?->name ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-neutral-500">Created</dt>
                    <dd class="mt-1 text-sm text-neutral-900">{{ $group->created_at->format('M j, Y') }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-neutral-500">Members</dt>
                    <dd class="mt-1 text-sm text-neutral-900">{{ $group->members_count }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-neutral-500">Events</dt>
                    <dd class="mt-1 text-sm text-neutral-900">{{ $group->events_count }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-neutral-500">Visibility</dt>
                    <dd class="mt-1 text-sm text-neutral-900">{{ $group->visibility instanceof \App\Enums\GroupVisibility ? $group->visibility->value : $group->visibility }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-neutral-500">Location</dt>
                    <dd class="mt-1 text-sm text-neutral-900">{{ $group->location ?? '—' }}</dd>
                </div>
                @if ($group->description)
                    <div class="sm:col-span-2">
                        <dt class="text-sm font-medium text-neutral-500">Description</dt>
                        <dd class="mt-1 text-sm text-neutral-900">{{ $group->description }}</dd>
                    </div>
                @endif
            </dl>
        </div>

        {{-- Actions --}}
        <div class="mt-6 flex flex-wrap gap-3">
            <form method="POST" action="{{ route('admin.groups.destroy', $group) }}" onsubmit="return confirm('Are you sure you want to permanently delete this group? This action cannot be undone.')">
                @csrf
                @method('DELETE')
                <button type="submit" class="rounded-lg bg-red-500 px-4 py-2 text-sm font-medium text-white hover:bg-red-600">
                    Delete Group
                </button>
            </form>
        </div>
    </div>
</x-layouts.app>
