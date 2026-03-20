<x-layouts.app title="Transfer Ownership — {{ $group->name }}" description="Transfer group ownership to a co-organizer.">
    <div class="mx-auto max-w-3xl px-4 py-10">
        <div class="mb-2">
            <a href="{{ route('groups.show', $group) }}" class="text-sm text-green-600 hover:text-green-700">&larr; Back to {{ $group->name }}</a>
        </div>

        <h1 class="text-2xl font-medium text-neutral-900">Transfer Ownership</h1>
        <p class="mt-1 text-sm text-neutral-500">Transfer primary organizer status to a co-organizer. You will become a co-organizer.</p>

        @if (session('status'))
            <div class="mt-6 rounded-md bg-green-50 px-4 py-3 text-sm text-green-700">
                {{ session('status') }}
            </div>
        @endif

        @if ($coOrganizers->isEmpty())
            <div class="mt-8 rounded-md bg-neutral-50 px-4 py-8 text-center text-sm text-neutral-500">
                No co-organizers available. You must first promote a member to co-organizer before transferring ownership.
            </div>
        @else
            <form method="POST" action="{{ route('groups.manage.transfer.update', $group) }}" class="mt-8 space-y-6">
                @csrf

                {{-- New Owner --}}
                <div>
                    <label for="new_owner_id" class="block text-sm font-medium text-neutral-700">New Owner <span class="text-red-500">*</span></label>
                    <select
                        id="new_owner_id"
                        name="new_owner_id"
                        required
                        class="mt-1 block w-full rounded-md border border-neutral-200 px-3 py-2 text-sm text-neutral-900 focus:border-green-500 focus:ring-1 focus:ring-green-500 focus:outline-none"
                    >
                        <option value="">Select a co-organizer</option>
                        @foreach ($coOrganizers as $coOrganizer)
                            <option value="{{ $coOrganizer->id }}" @selected(old('new_owner_id') == $coOrganizer->id)>
                                {{ $coOrganizer->name }} ({{ $coOrganizer->email }})
                            </option>
                        @endforeach
                    </select>
                    @error('new_owner_id')
                        <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Password Confirmation --}}
                <div>
                    <label for="password" class="block text-sm font-medium text-neutral-700">Confirm Your Password <span class="text-red-500">*</span></label>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        required
                        class="mt-1 block w-full rounded-md border border-neutral-200 px-3 py-2 text-sm text-neutral-900 placeholder-neutral-400 focus:border-green-500 focus:ring-1 focus:ring-green-500 focus:outline-none"
                    />
                    @error('password')
                        <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <div class="rounded-md bg-coral-50 px-4 py-3 text-sm text-coral-900">
                    <strong>Warning:</strong> This action will transfer your primary organizer role to the selected co-organizer. You will be demoted to co-organizer.
                </div>

                <div class="flex justify-end">
                    <button
                        type="submit"
                        class="rounded-md bg-coral-500 px-4 py-2 text-sm font-medium text-white hover:bg-coral-900 focus:ring-2 focus:ring-coral-500 focus:ring-offset-2 focus:outline-none"
                    >
                        Transfer Ownership
                    </button>
                </div>
            </form>
        @endif
    </div>
</x-layouts.app>
