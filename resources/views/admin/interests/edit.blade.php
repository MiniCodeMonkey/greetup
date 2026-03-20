<x-layouts.app :title="$seoTitle">
    <div class="mx-auto max-w-2xl px-4 py-8 sm:px-6">
        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-medium text-neutral-900">Edit Interest</h1>
            <a href="{{ route('admin.interests.index') }}" class="text-sm text-green-500 hover:text-green-700">&larr; Back to Interests</a>
        </div>

        <div class="mt-6 rounded-lg bg-white p-6 shadow-sm" style="border: 0.5px solid var(--color-neutral-200)">
            <form method="POST" action="{{ route('admin.interests.update', $interest) }}">
                @csrf
                @method('PUT')

                <div>
                    <label for="name" class="block text-sm font-medium text-neutral-700">Name</label>
                    <input
                        type="text"
                        name="name"
                        id="name"
                        value="{{ old('name', $interest->name) }}"
                        class="mt-1 w-full rounded-lg border px-4 py-2 text-sm text-neutral-700 focus:outline-none focus:ring-2 focus:ring-green-500"
                        style="border-color: var(--color-neutral-300)"
                        required
                    >
                    @error('name')
                        <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <div class="mt-6 flex items-center gap-3">
                    <button type="submit" class="rounded-lg bg-green-500 px-4 py-2 text-sm font-medium text-white hover:bg-green-600">
                        Update Interest
                    </button>
                    <a href="{{ route('admin.interests.index') }}" class="text-sm text-neutral-500 hover:text-neutral-700">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</x-layouts.app>
