<x-layouts.app title="Join Requests — {{ $group->name }}" description="Manage join requests.">
    <div class="mx-auto max-w-4xl px-4 py-10">
        <div class="mb-2">
            <a href="{{ route('groups.show', $group) }}" class="text-sm text-green-600 hover:text-green-700">&larr; Back to {{ $group->name }}</a>
        </div>

        <h1 class="text-2xl font-medium text-neutral-900">Join Requests</h1>
        <p class="mt-1 text-sm text-neutral-500">Review pending requests to join your group.</p>

        @if (session('status'))
            <div class="mt-6 rounded-md bg-green-50 px-4 py-3 text-sm text-green-700">
                {{ session('status') }}
            </div>
        @endif

        <div class="mt-6 space-y-4">
            @forelse ($requests as $joinRequest)
                <div class="flex items-center justify-between rounded-lg border border-neutral-200 bg-white px-4 py-4">
                    <div>
                        <p class="text-sm font-medium text-neutral-900">{{ $joinRequest->user->name }}</p>
                        <p class="text-xs text-neutral-500">Requested {{ $joinRequest->created_at->diffForHumans() }}</p>
                    </div>
                    <div class="flex items-center gap-2">
                        <form method="POST" action="{{ route('groups.manage.requests.approve', [$group, $joinRequest]) }}">
                            @csrf
                            <button type="submit" class="rounded-md bg-green-500 px-3 py-1.5 text-sm font-medium text-white hover:bg-green-600">Approve</button>
                        </form>
                        <form method="POST" action="{{ route('groups.manage.requests.deny', [$group, $joinRequest]) }}">
                            @csrf
                            <button type="submit" class="rounded-md bg-red-50 px-3 py-1.5 text-sm font-medium text-red-500 hover:bg-red-100">Deny</button>
                        </form>
                    </div>
                </div>
            @empty
                <div class="rounded-lg border border-neutral-200 bg-white px-4 py-8 text-center text-sm text-neutral-500">
                    No pending join requests.
                </div>
            @endforelse
        </div>

        <div class="mt-4">
            {{ $requests->links() }}
        </div>
    </div>
</x-layouts.app>
