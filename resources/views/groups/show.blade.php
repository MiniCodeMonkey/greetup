<x-layouts.app :title="$group->name" :description="Str::limit($group->description, 160)">
    <div class="mx-auto max-w-4xl px-4 py-10">
        @if (session('status'))
            <div class="mb-6 rounded-md bg-green-50 px-4 py-3 text-sm text-green-700">
                {{ session('status') }}
            </div>
        @endif

        <h1 class="text-2xl font-medium text-neutral-900">{{ $group->name }}</h1>

        @if ($group->location)
            <p class="mt-1 text-sm text-neutral-500">{{ $group->location }}</p>
        @endif

        @if ($group->description_html)
            <div class="prose prose-sm mt-6 max-w-none">
                {!! $group->description_html !!}
            </div>
        @endif
    </div>
</x-layouts.app>
