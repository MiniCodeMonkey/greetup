<x-layouts.app :title="$seoTitle">
    <div class="mx-auto max-w-6xl px-4 py-8 sm:px-6">
        <h1 class="text-2xl font-medium text-neutral-900">Admin Dashboard</h1>

        {{-- Stats --}}
        <div class="mt-6 grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-5">
            <div class="rounded-lg bg-white p-4 shadow-sm" style="border: 0.5px solid var(--color-neutral-200)">
                <p class="text-sm text-neutral-500">Total Users</p>
                <p class="mt-1 text-2xl font-medium text-neutral-900" data-testid="stat-total-users">{{ number_format($stats['totalUsers']) }}</p>
            </div>
            <div class="rounded-lg bg-white p-4 shadow-sm" style="border: 0.5px solid var(--color-neutral-200)">
                <p class="text-sm text-neutral-500">Total Groups</p>
                <p class="mt-1 text-2xl font-medium text-neutral-900" data-testid="stat-total-groups">{{ number_format($stats['totalGroups']) }}</p>
            </div>
            <div class="rounded-lg bg-white p-4 shadow-sm" style="border: 0.5px solid var(--color-neutral-200)">
                <p class="text-sm text-neutral-500">Total Events</p>
                <p class="mt-1 text-2xl font-medium text-neutral-900" data-testid="stat-total-events">{{ number_format($stats['totalEvents']) }}</p>
            </div>
            <div class="rounded-lg bg-white p-4 shadow-sm" style="border: 0.5px solid var(--color-neutral-200)">
                <p class="text-sm text-neutral-500">Events This Month</p>
                <p class="mt-1 text-2xl font-medium text-neutral-900" data-testid="stat-events-this-month">{{ number_format($stats['eventsThisMonth']) }}</p>
            </div>
            <div class="rounded-lg bg-white p-4 shadow-sm" style="border: 0.5px solid var(--color-neutral-200)">
                <p class="text-sm text-neutral-500">New Users This Week</p>
                <p class="mt-1 text-2xl font-medium text-neutral-900" data-testid="stat-new-users-this-week">{{ number_format($stats['newUsersThisWeek']) }}</p>
            </div>
        </div>

        <div class="mt-8 grid gap-8 lg:grid-cols-2">
            {{-- Recent Reports --}}
            <div>
                <h2 class="text-lg font-medium text-neutral-900">Recent Reports Needing Review</h2>
                @if ($recentReports->isEmpty())
                    <p class="mt-3 text-sm text-neutral-500">No pending reports.</p>
                @else
                    <div class="mt-3 space-y-3">
                        @foreach ($recentReports as $report)
                            <div class="rounded-lg bg-white p-4 shadow-sm" style="border: 0.5px solid var(--color-neutral-200)">
                                <div class="flex items-start justify-between">
                                    <div>
                                        <p class="text-sm font-medium text-neutral-900">{{ $report->reason->value }}</p>
                                        <p class="mt-1 text-xs text-neutral-500">
                                            Reported by {{ $report->reporter->name }} &middot; {{ $report->created_at->diffForHumans() }}
                                        </p>
                                        @if ($report->description)
                                            <p class="mt-1 text-sm text-neutral-600">{{ Str::limit($report->description, 100) }}</p>
                                        @endif
                                    </div>
                                    <span class="inline-flex items-center rounded-full bg-gold-50 px-2 py-0.5 text-xs font-medium text-gold-900">
                                        {{ $report->status->value }}
                                    </span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- Recently Created Groups --}}
            <div>
                <h2 class="text-lg font-medium text-neutral-900">Recently Created Groups</h2>
                @if ($recentGroups->isEmpty())
                    <p class="mt-3 text-sm text-neutral-500">No groups yet.</p>
                @else
                    <div class="mt-3 space-y-3">
                        @foreach ($recentGroups as $group)
                            <div class="rounded-lg bg-white p-4 shadow-sm" style="border: 0.5px solid var(--color-neutral-200)">
                                <a href="{{ route('groups.show', $group) }}" class="text-sm font-medium text-green-500 hover:text-green-700">{{ $group->name }}</a>
                                <p class="mt-1 text-xs text-neutral-500">
                                    by {{ $group->organizer->name }} &middot; {{ $group->created_at->diffForHumans() }}
                                </p>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        {{-- Quick Links --}}
        <div class="mt-8">
            <h2 class="text-lg font-medium text-neutral-900">Quick Links</h2>
            <div class="mt-3 flex flex-wrap gap-3">
                <a href="{{ route('admin.dashboard') }}" class="inline-flex items-center rounded-lg bg-white px-4 py-2 text-sm font-medium text-neutral-700 shadow-sm hover:bg-neutral-50" style="border: 0.5px solid var(--color-neutral-200)">
                    Manage Users
                </a>
                <a href="{{ route('admin.dashboard') }}" class="inline-flex items-center rounded-lg bg-white px-4 py-2 text-sm font-medium text-neutral-700 shadow-sm hover:bg-neutral-50" style="border: 0.5px solid var(--color-neutral-200)">
                    Manage Groups
                </a>
                <a href="{{ route('admin.dashboard') }}" class="inline-flex items-center rounded-lg bg-white px-4 py-2 text-sm font-medium text-neutral-700 shadow-sm hover:bg-neutral-50" style="border: 0.5px solid var(--color-neutral-200)">
                    Manage Reports
                </a>
                <a href="{{ route('admin.dashboard') }}" class="inline-flex items-center rounded-lg bg-white px-4 py-2 text-sm font-medium text-neutral-700 shadow-sm hover:bg-neutral-50" style="border: 0.5px solid var(--color-neutral-200)">
                    Settings
                </a>
                <a href="{{ route('admin.dashboard') }}" class="inline-flex items-center rounded-lg bg-white px-4 py-2 text-sm font-medium text-neutral-700 shadow-sm hover:bg-neutral-50" style="border: 0.5px solid var(--color-neutral-200)">
                    Manage Interests
                </a>
            </div>
        </div>
    </div>
</x-layouts.app>
