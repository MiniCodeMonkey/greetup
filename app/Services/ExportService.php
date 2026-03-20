<?php

namespace App\Services;

use App\Enums\RsvpStatus;
use App\Models\Event;
use App\Models\Group;

class ExportService
{
    /**
     * Generate CSV content for group members.
     *
     * Columns: name, email, joined date, attendance stats (events attended count).
     */
    public function exportMembers(Group $group): string
    {
        $members = $group->members()
            ->withPivot('joined_at')
            ->get();

        $eventIds = $group->events()->pluck('id');

        $handle = fopen('php://temp', 'r+');
        fputcsv($handle, ['Name', 'Email', 'Joined Date', 'Events Attended']);

        foreach ($members as $member) {
            $attendedCount = 0;

            if ($eventIds->isNotEmpty()) {
                $attendedCount = $member->rsvps()
                    ->whereIn('event_id', $eventIds)
                    ->where('status', RsvpStatus::Going)
                    ->count();
            }

            $joinedAt = $member->pivot->joined_at;
            $joinedDate = $joinedAt ? $joinedAt->format('Y-m-d') : '';

            fputcsv($handle, [
                $member->name,
                $member->email,
                $joinedDate,
                $attendedCount,
            ]);
        }

        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        return $csv;
    }

    /**
     * Generate CSV content for event attendees.
     *
     * Columns: name, RSVP status, guest count, checked-in.
     */
    public function exportAttendees(Event $event): string
    {
        $rsvps = $event->rsvps()
            ->with('user')
            ->get();

        $handle = fopen('php://temp', 'r+');
        fputcsv($handle, ['Name', 'RSVP Status', 'Guest Count', 'Checked In']);

        foreach ($rsvps as $rsvp) {
            fputcsv($handle, [
                $rsvp->user->name,
                $rsvp->status->value,
                $rsvp->guest_count,
                $rsvp->checked_in ? 'Yes' : 'No',
            ]);
        }

        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        return $csv;
    }
}
