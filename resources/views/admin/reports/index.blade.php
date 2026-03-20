<x-layouts.app :title="$seoTitle">
    <div class="mx-auto max-w-6xl px-4 py-8 sm:px-6">
        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-medium text-neutral-900">Manage Reports</h1>
            <a href="{{ route('admin.dashboard') }}" class="text-sm text-green-500 hover:text-green-700">&larr; Back to Dashboard</a>
        </div>

        {{-- Status Filter --}}
        <form method="GET" action="{{ route('admin.reports.index') }}" class="mt-6 flex flex-wrap gap-3">
            <select
                name="status"
                class="rounded-lg border px-4 py-2 text-sm text-neutral-700 focus:outline-none focus:ring-2 focus:ring-green-500"
                style="border-color: var(--color-neutral-300)"
                onchange="this.form.submit()"
            >
                <option value="pending" {{ request('status', 'pending') === 'pending' ? 'selected' : '' }}>Pending</option>
                <option value="reviewed" {{ request('status') === 'reviewed' ? 'selected' : '' }}>Reviewed</option>
                <option value="resolved" {{ request('status') === 'resolved' ? 'selected' : '' }}>Resolved</option>
                <option value="dismissed" {{ request('status') === 'dismissed' ? 'selected' : '' }}>Dismissed</option>
            </select>
            <button type="submit" class="rounded-lg bg-green-500 px-4 py-2 text-sm font-medium text-white hover:bg-green-600">
                Filter
            </button>
            @if (request('status') && request('status') !== 'pending')
                <a href="{{ route('admin.reports.index') }}" class="inline-flex items-center rounded-lg px-4 py-2 text-sm text-neutral-500 hover:text-neutral-700">
                    Clear
                </a>
            @endif
        </form>

        {{-- Reports Table --}}
        <div class="mt-6 overflow-x-auto rounded-lg bg-white shadow-sm" style="border: 0.5px solid var(--color-neutral-200)">
            <table class="min-w-full divide-y divide-neutral-200">
                <thead class="bg-neutral-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-neutral-500">Reporter</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-neutral-500">Reported Item</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-neutral-500">Reason</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-neutral-500">Description</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-neutral-500">Date</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-neutral-500">Status</th>
                        <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-neutral-500">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-neutral-200">
                    @forelse ($reports as $report)
                        @php
                            $groupKey = $report->reportable_type . ':' . $report->reportable_id;
                            $reportCount = $groupedCounts[$groupKey] ?? null;
                        @endphp
                        <tr>
                            <td class="whitespace-nowrap px-4 py-3 text-sm text-neutral-900">
                                {{ $report->reporter?->name ?? 'Deleted User' }}
                            </td>
                            <td class="px-4 py-3 text-sm text-neutral-900">
                                <span class="font-medium">{{ class_basename($report->reportable_type) }}</span>
                                @if ($report->reportable)
                                    <span class="text-neutral-500">#{{ $report->reportable_id }}</span>
                                @else
                                    <span class="text-neutral-400">(deleted)</span>
                                @endif
                                @if ($reportCount)
                                    <span class="ml-1 inline-flex items-center rounded-full bg-red-50 px-2 py-0.5 text-xs font-medium text-red-900">{{ $reportCount }} reports</span>
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-sm text-neutral-600">
                                {{ str_replace('_', ' ', ucfirst($report->reason->value)) }}
                            </td>
                            <td class="max-w-xs truncate px-4 py-3 text-sm text-neutral-600">
                                {{ $report->description ?? '—' }}
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-sm text-neutral-500">
                                {{ $report->created_at->format('M j, Y') }}
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-sm">
                                @if ($report->status === \App\Enums\ReportStatus::Pending)
                                    <span class="inline-flex items-center rounded-full bg-gold-50 px-2 py-0.5 text-xs font-medium text-gold-900">Pending</span>
                                @elseif ($report->status === \App\Enums\ReportStatus::Reviewed)
                                    <span class="inline-flex items-center rounded-full bg-blue-50 px-2 py-0.5 text-xs font-medium text-blue-900">Reviewed</span>
                                @elseif ($report->status === \App\Enums\ReportStatus::Resolved)
                                    <span class="inline-flex items-center rounded-full bg-green-50 px-2 py-0.5 text-xs font-medium text-green-900">Resolved</span>
                                @else
                                    <span class="inline-flex items-center rounded-full bg-neutral-100 px-2 py-0.5 text-xs font-medium text-neutral-600">Dismissed</span>
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-right text-sm">
                                <div class="flex items-center justify-end gap-2">
                                    @if ($report->status === \App\Enums\ReportStatus::Pending)
                                        <form method="POST" action="{{ route('admin.reports.review', $report) }}" class="inline">
                                            @csrf
                                            <button type="submit" class="text-blue-600 hover:text-blue-800">Review</button>
                                        </form>
                                    @endif

                                    @if (in_array($report->status, [\App\Enums\ReportStatus::Pending, \App\Enums\ReportStatus::Reviewed]))
                                        <button
                                            type="button"
                                            class="text-green-600 hover:text-green-800"
                                            onclick="document.getElementById('resolve-form-{{ $report->id }}').classList.toggle('hidden')"
                                        >Resolve</button>
                                        <form method="POST" action="{{ route('admin.reports.dismiss', $report) }}" class="inline">
                                            @csrf
                                            <button type="submit" class="text-neutral-500 hover:text-neutral-700">Dismiss</button>
                                        </form>
                                    @endif

                                    @if (in_array($report->status, [\App\Enums\ReportStatus::Pending, \App\Enums\ReportStatus::Reviewed]))
                                        <button
                                            type="button"
                                            class="text-red-600 hover:text-red-800"
                                            onclick="document.getElementById('suspend-form-{{ $report->id }}').classList.toggle('hidden')"
                                        >Suspend User</button>

                                        @if ($report->reportable && ! ($report->reportable instanceof \App\Models\User))
                                            <form method="POST" action="{{ route('admin.reports.delete-content', $report) }}" class="inline">
                                                @csrf
                                                <button type="submit" class="text-red-600 hover:text-red-800" onclick="return confirm('Are you sure you want to delete this content?')">Delete Content</button>
                                            </form>
                                        @endif
                                    @endif
                                </div>
                            </td>
                        </tr>

                        {{-- Resolve form (hidden by default) --}}
                        @if (in_array($report->status, [\App\Enums\ReportStatus::Pending, \App\Enums\ReportStatus::Reviewed]))
                            <tr id="resolve-form-{{ $report->id }}" class="hidden bg-neutral-50">
                                <td colspan="7" class="px-4 py-3">
                                    <form method="POST" action="{{ route('admin.reports.resolve', $report) }}" class="flex items-end gap-3">
                                        @csrf
                                        <div class="flex-1">
                                            <label for="resolution_notes_{{ $report->id }}" class="block text-sm font-medium text-neutral-700">Resolution Notes</label>
                                            <textarea
                                                name="resolution_notes"
                                                id="resolution_notes_{{ $report->id }}"
                                                rows="2"
                                                required
                                                class="mt-1 w-full rounded-lg border px-3 py-2 text-sm text-neutral-700 focus:outline-none focus:ring-2 focus:ring-green-500"
                                                style="border-color: var(--color-neutral-300)"
                                                placeholder="Describe how this was resolved..."
                                            ></textarea>
                                        </div>
                                        <button type="submit" class="rounded-lg bg-green-500 px-4 py-2 text-sm font-medium text-white hover:bg-green-600">
                                            Resolve
                                        </button>
                                    </form>
                                </td>
                            </tr>

                            {{-- Suspend form (hidden by default) --}}
                            <tr id="suspend-form-{{ $report->id }}" class="hidden bg-red-50">
                                <td colspan="7" class="px-4 py-3">
                                    <form method="POST" action="{{ route('admin.reports.suspend-user', $report) }}" class="flex items-end gap-3">
                                        @csrf
                                        <div class="flex-1">
                                            <label for="suspend_reason_{{ $report->id }}" class="block text-sm font-medium text-neutral-700">Suspension Reason</label>
                                            <textarea
                                                name="reason"
                                                id="suspend_reason_{{ $report->id }}"
                                                rows="2"
                                                required
                                                class="mt-1 w-full rounded-lg border px-3 py-2 text-sm text-neutral-700 focus:outline-none focus:ring-2 focus:ring-red-500"
                                                style="border-color: var(--color-neutral-300)"
                                                placeholder="Reason for suspending the user..."
                                            ></textarea>
                                        </div>
                                        <button type="submit" class="rounded-lg bg-red-500 px-4 py-2 text-sm font-medium text-white hover:bg-red-600">
                                            Suspend
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @endif
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-8 text-center text-sm text-neutral-500">No reports found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        <div class="mt-4">
            {{ $reports->links() }}
        </div>
    </div>
</x-layouts.app>
