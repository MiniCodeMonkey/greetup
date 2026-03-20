<?php

namespace App\Http\Controllers\Events;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Group;
use App\Services\ExportService;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AttendeeManagementController extends Controller
{
    /**
     * Display the attendee management page.
     */
    public function index(Group $group, Event $event): View
    {
        Gate::authorize('manageAttendees', $event);

        return view('events.attendees', [
            'group' => $group,
            'event' => $event,
        ]);
    }

    /**
     * Export attendee list as CSV.
     */
    public function export(Group $group, Event $event, ExportService $exportService): StreamedResponse
    {
        Gate::authorize('manageAttendees', $event);

        $csv = $exportService->exportAttendees($event);

        return response()->streamDownload(function () use ($csv): void {
            echo $csv;
        }, "{$event->slug}-attendees.csv", [
            'Content-Type' => 'text/csv',
        ]);
    }
}
