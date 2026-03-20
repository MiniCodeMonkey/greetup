<x-layouts.app :title="$seoTitle">
    <div class="mx-auto max-w-6xl px-4 py-8 sm:px-6">
        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-medium text-neutral-900">{{ $user->name }}</h1>
            <a href="{{ route('admin.users.index') }}" class="text-sm text-green-500 hover:text-green-700">&larr; Back to Users</a>
        </div>

        {{-- Flash Messages --}}
        @if (session('success'))
            <div class="mt-4 rounded-lg bg-green-50 p-4 text-sm text-green-800">
                {{ session('success') }}
            </div>
        @endif

        {{-- User Details --}}
        <div class="mt-6 rounded-lg bg-white p-6 shadow-sm" style="border: 0.5px solid var(--color-neutral-200)">
            <dl class="grid gap-4 sm:grid-cols-2">
                <div>
                    <dt class="text-sm font-medium text-neutral-500">Email</dt>
                    <dd class="mt-1 text-sm text-neutral-900">{{ $user->email }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-neutral-500">Joined</dt>
                    <dd class="mt-1 text-sm text-neutral-900">{{ $user->created_at->format('M j, Y') }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-neutral-500">Groups</dt>
                    <dd class="mt-1 text-sm text-neutral-900">{{ $user->groups_count }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-neutral-500">Events Attended</dt>
                    <dd class="mt-1 text-sm text-neutral-900">{{ $user->rsvps_count }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-neutral-500">Status</dt>
                    <dd class="mt-1 text-sm">
                        @if ($user->is_suspended)
                            <span class="inline-flex items-center rounded-full bg-red-50 px-2 py-0.5 text-xs font-medium text-red-900">Suspended</span>
                        @else
                            <span class="inline-flex items-center rounded-full bg-green-50 px-2 py-0.5 text-xs font-medium text-green-900">Active</span>
                        @endif
                    </dd>
                </div>
                @if ($user->is_suspended && $user->suspended_reason)
                    <div>
                        <dt class="text-sm font-medium text-neutral-500">Suspension Reason</dt>
                        <dd class="mt-1 text-sm text-neutral-900">{{ $user->suspended_reason }}</dd>
                    </div>
                @endif
            </dl>
        </div>

        {{-- Actions --}}
        <div class="mt-6 flex flex-wrap gap-3">
            @if ($user->is_suspended)
                <form method="POST" action="{{ route('admin.users.unsuspend', $user) }}">
                    @csrf
                    <button type="submit" class="rounded-lg bg-green-500 px-4 py-2 text-sm font-medium text-white hover:bg-green-600">
                        Unsuspend User
                    </button>
                </form>
            @else
                <button
                    type="button"
                    onclick="document.getElementById('suspend-form').classList.toggle('hidden')"
                    class="rounded-lg bg-gold-500 px-4 py-2 text-sm font-medium text-white hover:bg-gold-600"
                >
                    Suspend User
                </button>
            @endif

            <form method="POST" action="{{ route('admin.users.destroy', $user) }}" onsubmit="return confirm('Are you sure you want to permanently delete this user? This action cannot be undone.')">
                @csrf
                @method('DELETE')
                <button type="submit" class="rounded-lg bg-red-500 px-4 py-2 text-sm font-medium text-white hover:bg-red-600">
                    Delete User
                </button>
            </form>
        </div>

        {{-- Suspend Form --}}
        @unless ($user->is_suspended)
            <form id="suspend-form" method="POST" action="{{ route('admin.users.suspend', $user) }}" class="mt-4 hidden rounded-lg bg-white p-6 shadow-sm" style="border: 0.5px solid var(--color-neutral-200)">
                @csrf
                <label for="reason" class="block text-sm font-medium text-neutral-700">Suspension Reason</label>
                <textarea
                    name="reason"
                    id="reason"
                    rows="3"
                    required
                    class="mt-1 w-full rounded-lg border px-4 py-2 text-sm text-neutral-700 focus:outline-none focus:ring-2 focus:ring-green-500"
                    style="border-color: var(--color-neutral-300)"
                    placeholder="Provide a reason for suspending this user..."
                >{{ old('reason') }}</textarea>
                @error('reason')
                    <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                @enderror
                <button type="submit" class="mt-3 rounded-lg bg-gold-500 px-4 py-2 text-sm font-medium text-white hover:bg-gold-600">
                    Confirm Suspension
                </button>
            </form>
        @endunless

        {{-- Groups --}}
        <div class="mt-8">
            <h2 class="text-lg font-medium text-neutral-900">Groups</h2>
            @if ($groups->isEmpty())
                <p class="mt-3 text-sm text-neutral-500">This user is not a member of any groups.</p>
            @else
                <div class="mt-3 space-y-2">
                    @foreach ($groups as $group)
                        <div class="rounded-lg bg-white p-4 shadow-sm" style="border: 0.5px solid var(--color-neutral-200)">
                            <a href="{{ route('groups.show', $group) }}" class="text-sm font-medium text-green-500 hover:text-green-700">{{ $group->name }}</a>
                            <span class="ml-2 text-xs text-neutral-500">{{ $group->pivot->role instanceof \App\Enums\GroupRole ? $group->pivot->role->value : $group->pivot->role }}</span>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Events Attended --}}
        <div class="mt-8">
            <h2 class="text-lg font-medium text-neutral-900">Events Attended</h2>
            @if ($rsvps->isEmpty())
                <p class="mt-3 text-sm text-neutral-500">This user has not attended any events.</p>
            @else
                <div class="mt-3 space-y-2">
                    @foreach ($rsvps as $rsvp)
                        <div class="rounded-lg bg-white p-4 shadow-sm" style="border: 0.5px solid var(--color-neutral-200)">
                            @if ($rsvp->event)
                                <a href="{{ route('events.show', [$rsvp->event->group, $rsvp->event]) }}" class="text-sm font-medium text-green-500 hover:text-green-700">{{ $rsvp->event->name }}</a>
                                <span class="ml-2 text-xs text-neutral-500">{{ $rsvp->event->group->name ?? '' }}</span>
                            @else
                                <span class="text-sm text-neutral-500">Event deleted</span>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</x-layouts.app>
