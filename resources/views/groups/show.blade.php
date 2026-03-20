<x-layouts.app :title="$seoTitle" :description="$seoDescription" :seoImage="$seoImage">
    {{-- Cover photo / decorative header --}}
    @if ($coverPhoto)
        <div class="relative h-48 w-full overflow-hidden bg-green-900 sm:h-64">
            <img src="{{ $coverPhoto }}" alt="{{ $group->name }} cover photo" class="h-full w-full object-cover">
        </div>
    @else
        <div class="relative h-48 w-full overflow-hidden bg-green-900 sm:h-64">
            <x-blob color="#1FAF63" :size="300" :opacity="0.15" shape="cloud" class="left-10 top-4" />
            <x-blob color="#7C5CFC" :size="200" :opacity="0.1" shape="circle" class="right-20 top-10" />
            <x-blob color="#FF6B4A" :size="250" :opacity="0.1" shape="cloud" class="bottom-0 left-1/3" />
        </div>
    @endif

    <div class="mx-auto max-w-4xl px-4 py-8">
        @if (session('status'))
            <div class="mb-6 rounded-md bg-green-50 px-4 py-3 text-sm text-green-700">
                {{ session('status') }}
            </div>
        @endif

        {{-- Group info header --}}
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div class="min-w-0 flex-1">
                <h1 class="text-[22px] font-medium text-neutral-900">{{ $group->name }}</h1>

                <div class="mt-2 flex flex-wrap items-center gap-x-4 gap-y-2 text-sm text-neutral-500">
                    @if ($group->location)
                        <span>{{ $group->location }}</span>
                    @endif
                    <span class="flex items-center gap-2">
                        <x-avatar-stack :users="$memberAvatars" :max="5" size="sm" />
                        <span>{{ $group->members_count }} {{ Str::plural('member', $group->members_count) }}</span>
                    </span>
                </div>

                @if ($topics->isNotEmpty())
                    <div class="mt-3 flex flex-wrap gap-2">
                        @foreach ($topics as $topic)
                            <x-pill :tag="$topic" />
                        @endforeach
                    </div>
                @endif

                @if ($group->organizer)
                    <p class="mt-3 text-sm text-neutral-500">
                        Organized by
                        <a href="{{ route('members.show', $group->organizer) }}" class="font-medium text-neutral-700 hover:text-green-500">{{ $group->organizer->name }}</a>
                    </p>
                @endif
            </div>

            {{-- Action buttons --}}
            <div class="shrink-0">
                @auth
                    @if ($isMember)
                        <form method="POST" action="{{ route('groups.show', $group) }}" data-testid="leave-form">
                            @csrf
                            @php
                                $canLeave = $membership->role instanceof \App\Enums\GroupRole
                                    ? $membership->role !== \App\Enums\GroupRole::Organizer
                                    : $membership->role !== \App\Enums\GroupRole::Organizer->value;
                            @endphp
                            @if ($canLeave)
                                <button
                                    type="button"
                                    class="inline-flex items-center rounded-md px-4 py-2 text-sm font-medium text-neutral-700 hover:bg-neutral-100"
                                    style="border: 0.5px solid var(--color-neutral-200)"
                                    data-testid="leave-button"
                                    onclick="if(confirm('Are you sure you want to leave this group?')) { this.closest('form').submit(); }"
                                >
                                    Leave Group
                                </button>
                            @endif
                        </form>
                    @else
                        @if ($pendingRequest)
                            <span
                                class="inline-flex items-center rounded-md bg-neutral-100 px-4 py-2 text-sm font-medium text-neutral-500"
                                data-testid="request-pending"
                            >
                                Request Pending
                            </span>
                        @elseif ($group->requires_approval)
                            <button
                                type="button"
                                class="inline-flex items-center rounded-md bg-green-500 px-4 py-2 text-sm font-medium text-white hover:bg-green-700"
                                data-testid="request-join-button"
                                onclick="document.getElementById('join-request-form').classList.toggle('hidden')"
                            >
                                Request to Join
                            </button>
                        @else
                            <form method="POST" action="{{ route('groups.join', $group) }}">
                                @csrf
                                <button
                                    type="submit"
                                    class="inline-flex items-center rounded-md bg-green-500 px-4 py-2 text-sm font-medium text-white hover:bg-green-700"
                                    data-testid="join-button"
                                >
                                    Join Group
                                </button>
                            </form>
                        @endif
                    @endauth
                @endauth

                @guest
                    <a
                        href="{{ route('login') }}"
                        class="inline-flex items-center rounded-md bg-green-500 px-4 py-2 text-sm font-medium text-white hover:bg-green-700"
                        data-testid="join-button"
                    >
                        Join Group
                    </a>
                @endguest
            </div>
        </div>

        {{-- Join request form with membership questions --}}
        @auth
            @if (! $isMember && ! $pendingRequest && $group->requires_approval)
                <div id="join-request-form" class="mt-6 hidden rounded-lg bg-neutral-50 p-6" data-testid="join-request-form">
                    <h2 class="text-base font-medium text-neutral-900">Request to Join {{ $group->name }}</h2>
                    <form method="POST" action="{{ route('groups.request-join', $group) }}" class="mt-4">
                        @csrf
                        @if ($membershipQuestions->isNotEmpty())
                            <div class="space-y-4">
                                @foreach ($membershipQuestions as $question)
                                    <div>
                                        <label for="answer-{{ $question->id }}" class="block text-sm font-medium text-neutral-700">
                                            {{ $question->question }}
                                            @if ($question->is_required)
                                                <span class="text-red-500">*</span>
                                            @endif
                                        </label>
                                        <textarea
                                            id="answer-{{ $question->id }}"
                                            name="answers[{{ $question->id }}]"
                                            rows="3"
                                            class="mt-1 block w-full rounded-md border-neutral-200 text-sm shadow-sm focus:border-green-500 focus:ring-green-500"
                                            {{ $question->is_required ? 'required' : '' }}
                                        >{{ old("answers.{$question->id}") }}</textarea>
                                        @error("answers.{$question->id}")
                                            <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                                        @enderror
                                    </div>
                                @endforeach
                            </div>
                        @endif
                        <div class="mt-4">
                            <button
                                type="submit"
                                class="inline-flex items-center rounded-md bg-green-500 px-4 py-2 text-sm font-medium text-white hover:bg-green-700"
                                data-testid="submit-join-request"
                            >
                                Submit Request
                            </button>
                        </div>
                    </form>
                </div>
            @endif
        @endauth

        @if ($isPrivate && ! $isMember)
            {{-- Private group: limited info --}}
            <div class="mt-8">
                @if ($group->description_html)
                    <div class="prose prose-sm max-w-none text-neutral-700">
                        {!! $group->description_html !!}
                    </div>
                @endif

                <div class="mt-6 rounded-lg bg-neutral-50 px-6 py-8 text-center">
                    <p class="text-sm text-neutral-500">This is a private group. Join to see events, discussions, and members.</p>
                </div>
            </div>
        @else
            {{-- Description --}}
            @if ($group->description_html)
                <div class="prose prose-sm mt-6 max-w-none text-neutral-700">
                    {!! $group->description_html !!}
                </div>
            @endif

            {{-- Tab bar --}}
            <div class="mt-8">
                <x-tab-bar :tabs="[
                    ['label' => 'Upcoming Events', 'href' => route('groups.show', ['group' => $group->slug, 'tab' => 'upcoming']), 'active' => $tab === 'upcoming'],
                    ['label' => 'Past Events', 'href' => route('groups.show', ['group' => $group->slug, 'tab' => 'past']), 'active' => $tab === 'past'],
                    ['label' => 'Discussions', 'href' => route('groups.show', ['group' => $group->slug, 'tab' => 'discussions']), 'active' => $tab === 'discussions'],
                    ['label' => 'Members', 'href' => route('groups.show', ['group' => $group->slug, 'tab' => 'members']), 'active' => $tab === 'members'],
                    ['label' => 'About', 'href' => route('groups.show', ['group' => $group->slug, 'tab' => 'about']), 'active' => $tab === 'about'],
                ]" />
            </div>

            {{-- Tab content --}}
            <div class="mt-6">
                @if ($tab === 'upcoming')
                    @if ($upcomingEvents->isEmpty())
                        <p class="text-sm text-neutral-500" data-testid="no-upcoming-events">No upcoming events yet.</p>
                    @else
                        <div class="space-y-4" data-testid="upcoming-events-list">
                            @foreach ($upcomingEvents as $event)
                                <div class="rounded-lg px-4 py-3" style="border: 0.5px solid var(--color-neutral-200)">
                                    <h3 class="text-sm font-medium text-neutral-900">{{ $event->name }}</h3>
                                    <p class="mt-1 text-xs text-neutral-500">{{ $event->starts_at->format('M j, Y \a\t g:i A') }}</p>
                                </div>
                            @endforeach
                        </div>
                    @endif
                @endif

                @if ($tab === 'past')
                    @if ($pastEvents->isEmpty())
                        <p class="text-sm text-neutral-500" data-testid="no-past-events">No past events.</p>
                    @else
                        <div class="space-y-4" data-testid="past-events-list">
                            @foreach ($pastEvents as $event)
                                <div class="rounded-lg px-4 py-3" style="border: 0.5px solid var(--color-neutral-200)">
                                    <div class="flex items-center gap-2">
                                        <h3 class="text-sm font-medium text-neutral-900">{{ $event->name }}</h3>
                                        @if ($event->status === \App\Enums\EventStatus::Cancelled)
                                            <span class="inline-flex items-center rounded-full bg-red-50 px-2 py-0.5 text-xs font-medium text-red-900" data-testid="cancelled-badge">Cancelled</span>
                                        @endif
                                    </div>
                                    <p class="mt-1 text-xs text-neutral-500">{{ $event->starts_at->format('M j, Y \a\t g:i A') }}</p>
                                </div>
                            @endforeach
                        </div>
                    @endif
                @endif

                @if ($tab === 'discussions')
                    @auth
                        @if ($isMember)
                            <div class="mb-4">
                                <a href="{{ route('discussions.create', $group) }}"
                                   class="inline-flex items-center rounded-md bg-green-500 px-4 py-2 text-sm font-medium text-white hover:bg-green-700"
                                   data-testid="create-discussion-button"
                                >
                                    New Discussion
                                </a>
                            </div>
                        @endif
                    @endauth

                    @if ($discussions->isEmpty())
                        <p class="text-sm text-neutral-500" data-testid="no-discussions">No discussions yet.</p>
                    @else
                        <div class="space-y-4" data-testid="discussions-list">
                            @foreach ($discussions as $discussion)
                                <div class="rounded-lg px-4 py-3" style="border: 0.5px solid var(--color-neutral-200)">
                                    <div class="flex items-center gap-2">
                                        <h3 class="text-sm font-medium text-neutral-900">{{ $discussion->title }}</h3>
                                        @if ($discussion->is_pinned)
                                            <span class="inline-flex items-center rounded-full bg-green-50 px-2 py-0.5 text-xs font-medium text-green-700" data-testid="pinned-badge">Pinned</span>
                                        @endif
                                    </div>
                                    <p class="mt-1 text-xs text-neutral-500">by {{ $discussion->author->name ?? 'Unknown' }}</p>
                                </div>
                            @endforeach
                        </div>

                        @if ($discussions->hasPages())
                            <div class="mt-6">
                                {{ $discussions->appends(['tab' => 'discussions'])->links() }}
                            </div>
                        @endif
                    @endif
                @endif

                @if ($tab === 'members')
                    @if ($allMembers->isEmpty())
                        <p class="text-sm text-neutral-500">No members yet.</p>
                    @else
                        <div class="grid grid-cols-2 gap-4 sm:grid-cols-3" data-testid="members-list">
                            @foreach ($allMembers as $member)
                                <a href="{{ route('members.show', $member) }}" class="flex items-center gap-3 rounded-lg px-3 py-3 hover:bg-neutral-50">
                                    <x-avatar :user="$member" size="md" />
                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-medium text-neutral-900">{{ $member->name }}</p>
                                        @php
                                            $role = $member->pivot->role instanceof \App\Enums\GroupRole
                                                ? $member->pivot->role
                                                : \App\Enums\GroupRole::from($member->pivot->role);
                                        @endphp
                                        @if ($role !== \App\Enums\GroupRole::Member)
                                            <p class="text-xs text-neutral-500">{{ str_replace('_', ' ', ucfirst($role->value)) }}</p>
                                        @endif
                                    </div>
                                </a>
                            @endforeach
                        </div>
                    @endif
                @endif

                @if ($tab === 'about')
                    <div class="space-y-8">
                        @if ($group->description_html)
                            <div>
                                <h2 class="text-sm font-medium text-neutral-500">Description</h2>
                                <div class="prose prose-sm mt-2 max-w-none text-neutral-700">
                                    {!! $group->description_html !!}
                                </div>
                            </div>
                        @endif

                        @if ($group->location)
                            <div>
                                <h2 class="text-sm font-medium text-neutral-500">Location</h2>
                                <p class="mt-2 text-sm text-neutral-700">{{ $group->location }}</p>
                            </div>
                        @endif

                        @if ($leadershipTeam->isNotEmpty())
                            <div data-testid="leadership-team">
                                <h2 class="text-sm font-medium text-neutral-500">Leadership Team</h2>
                                <div class="mt-3 space-y-3">
                                    @foreach ($leadershipTeam as $leader)
                                        <a href="{{ route('members.show', $leader) }}" class="flex items-center gap-3 rounded-lg px-3 py-3 hover:bg-neutral-50">
                                            <x-avatar :user="$leader" size="md" />
                                            <div class="min-w-0">
                                                <p class="truncate text-sm font-medium text-neutral-900">{{ $leader->name }}</p>
                                                @php
                                                    $leaderRole = $leader->pivot->role instanceof \App\Enums\GroupRole
                                                        ? $leader->pivot->role
                                                        : \App\Enums\GroupRole::from($leader->pivot->role);
                                                @endphp
                                                <p class="text-xs text-neutral-500">{{ str_replace('_', ' ', ucfirst($leaderRole->value)) }}</p>
                                            </div>
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>
                @endif
            </div>
        @endif
    </div>
</x-layouts.app>
