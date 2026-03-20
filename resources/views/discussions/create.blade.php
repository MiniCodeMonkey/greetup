<x-layouts.app title="New Discussion" description="Start a new discussion in {{ $group->name }}.">
    <div class="mx-auto max-w-3xl px-4 py-10">
        <h1 class="text-2xl font-medium text-neutral-900">New Discussion</h1>
        <p class="mt-1 text-sm text-neutral-500">Start a discussion in <strong>{{ $group->name }}</strong>.</p>

        <form method="POST" action="{{ route('discussions.store', $group) }}" class="mt-8 space-y-6">
            @csrf

            {{-- Title --}}
            <div>
                <label for="title" class="block text-sm font-medium text-neutral-700">Title <span class="text-red-500">*</span></label>
                <input
                    type="text"
                    id="title"
                    name="title"
                    value="{{ old('title') }}"
                    required
                    class="mt-1 block w-full rounded-md border border-neutral-200 px-3 py-2 text-sm text-neutral-900 placeholder-neutral-400 focus:border-green-500 focus:ring-1 focus:ring-green-500 focus:outline-none"
                    placeholder="What do you want to discuss?"
                />
                @error('title')
                    <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                @enderror
            </div>

            {{-- Body --}}
            <div>
                <label for="body" class="block text-sm font-medium text-neutral-700">Body <span class="text-red-500">*</span></label>
                <textarea
                    id="body"
                    name="body"
                    rows="8"
                    required
                    class="mt-1 block w-full rounded-md border border-neutral-200 px-3 py-2 text-sm text-neutral-900 placeholder-neutral-400 focus:border-green-500 focus:ring-1 focus:ring-green-500 focus:outline-none"
                    placeholder="Markdown is supported..."
                >{{ old('body') }}</textarea>
                @error('body')
                    <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex items-center gap-4">
                <button
                    type="submit"
                    class="inline-flex items-center rounded-md bg-green-500 px-4 py-2 text-sm font-medium text-white hover:bg-green-700"
                >
                    Create Discussion
                </button>
                <a href="{{ route('groups.show', ['group' => $group->slug, 'tab' => 'discussions']) }}" class="text-sm text-neutral-500 hover:text-neutral-700">Cancel</a>
            </div>
        </form>
    </div>
</x-layouts.app>
