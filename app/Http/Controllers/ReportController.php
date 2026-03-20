<?php

namespace App\Http\Controllers;

use App\Http\Requests\Reports\StoreReportRequest;
use App\Models\Report;
use App\Models\User;
use App\Notifications\ReportReceived;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Notification;

class ReportController extends Controller
{
    public function store(StoreReportRequest $request): RedirectResponse
    {
        $user = $request->user();
        $modelClass = $request->reportableModelClass();
        $reportableId = $request->input('reportable_id');

        // Verify the reportable item exists
        if (! $modelClass::query()->where('id', $reportableId)->exists()) {
            return back()->withErrors(['reportable_id' => 'The reported content could not be found.']);
        }

        // Check for existing active report (pending status) by this reporter on this item
        $existingReport = Report::query()
            ->where('reporter_id', $user->id)
            ->where('reportable_type', $modelClass)
            ->where('reportable_id', $reportableId)
            ->pending()
            ->exists();

        if ($existingReport) {
            return back()->withErrors(['report' => 'You have already reported this content.']);
        }

        $report = Report::create([
            'reporter_id' => $user->id,
            'reportable_type' => $modelClass,
            'reportable_id' => $reportableId,
            'reason' => $request->input('reason'),
            'description' => $request->input('description'),
        ]);

        // Notify all platform admins
        $admins = User::role('admin')->get();
        Notification::send($admins, new ReportReceived($report));

        return back()->with('status', 'Your report has been submitted. Thank you for helping keep our community safe.');
    }
}
