<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ReportStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ResolveReportRequest;
use App\Http\Requests\Admin\SuspendUserRequest;
use App\Models\Group;
use App\Models\Report;
use App\Models\Setting;
use App\Models\User;
use App\Notifications\AccountSuspended;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AdminReportController extends Controller
{
    public function index(Request $request): View
    {
        $query = Report::query()
            ->with(['reporter', 'reportable', 'reviewer']);

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        } else {
            $query->where('status', ReportStatus::Pending);
        }

        $reports = $query->latest()->paginate(25)->withQueryString();

        // Group reports by reportable item to show counts
        $groupedCounts = Report::query()
            ->where('status', ReportStatus::Pending)
            ->select('reportable_type', 'reportable_id', DB::raw('COUNT(*) as report_count'))
            ->groupBy('reportable_type', 'reportable_id')
            ->having('report_count', '>', 1)
            ->get()
            ->mapWithKeys(fn ($row) => [$row->reportable_type.':'.$row->reportable_id => $row->report_count]);

        $seoTitle = 'Admin: Reports — '.Setting::get('site_name', config('app.name', 'Greetup'));

        return view('admin.reports.index', compact('reports', 'groupedCounts', 'seoTitle'));
    }

    public function review(Report $report): RedirectResponse
    {
        if ($report->status !== ReportStatus::Pending) {
            return redirect()->route('admin.reports.index')
                ->with('error', 'Only pending reports can be marked as reviewed.');
        }

        $report->update([
            'status' => ReportStatus::Reviewed,
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
        ]);

        return redirect()->route('admin.reports.index')
            ->with('success', 'Report marked as reviewed.');
    }

    public function resolve(ResolveReportRequest $request, Report $report): RedirectResponse
    {
        if (! in_array($report->status, [ReportStatus::Pending, ReportStatus::Reviewed])) {
            return redirect()->route('admin.reports.index')
                ->with('error', 'Only pending or reviewed reports can be resolved.');
        }

        $report->update([
            'status' => ReportStatus::Resolved,
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
            'resolution_notes' => $request->validated('resolution_notes'),
        ]);

        return redirect()->route('admin.reports.index')
            ->with('success', 'Report resolved.');
    }

    public function dismiss(Report $report): RedirectResponse
    {
        if (! in_array($report->status, [ReportStatus::Pending, ReportStatus::Reviewed])) {
            return redirect()->route('admin.reports.index')
                ->with('error', 'Only pending or reviewed reports can be dismissed.');
        }

        $report->update([
            'status' => ReportStatus::Dismissed,
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
        ]);

        return redirect()->route('admin.reports.index')
            ->with('success', 'Report dismissed.');
    }

    public function suspendUser(SuspendUserRequest $request, Report $report): RedirectResponse
    {
        $user = $this->resolveReportableUser($report);

        if (! $user) {
            return redirect()->route('admin.reports.index')
                ->with('error', 'Cannot suspend: reported content does not have an associated user.');
        }

        $user->update([
            'is_suspended' => true,
            'suspended_at' => now(),
            'suspended_reason' => $request->validated('reason'),
        ]);

        $user->notify(new AccountSuspended($request->validated('reason')));

        return redirect()->route('admin.reports.index')
            ->with('success', "User {$user->name} has been suspended.");
    }

    public function deleteContent(Report $report): RedirectResponse
    {
        $reportable = $report->reportable;

        if (! $reportable) {
            return redirect()->route('admin.reports.index')
                ->with('error', 'Reported content not found.');
        }

        $type = class_basename($reportable);

        if ($reportable instanceof User) {
            return redirect()->route('admin.reports.index')
                ->with('error', 'Use the suspend action for users.');
        }

        if (method_exists($reportable, 'trashed') && ! $reportable->trashed()) {
            $reportable->delete();
        } elseif (! method_exists($reportable, 'trashed')) {
            $reportable->delete();
        }

        return redirect()->route('admin.reports.index')
            ->with('success', "{$type} has been deleted.");
    }

    /**
     * Resolve the user associated with the reported content.
     */
    private function resolveReportableUser(Report $report): ?User
    {
        $reportable = $report->reportable;

        if ($reportable instanceof User) {
            return $reportable;
        }

        if (method_exists($reportable, 'user')) {
            return $reportable->user;
        }

        if (method_exists($reportable, 'organizer')) {
            return $reportable->organizer;
        }

        return null;
    }
}
