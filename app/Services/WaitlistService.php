<?php

namespace App\Services;

use App\Enums\EventStatus;
use App\Enums\RsvpStatus;
use App\Models\Event;
use App\Models\Rsvp;
use App\Notifications\PromotedFromWaitlist;

class WaitlistService
{
    /**
     * Promote the next eligible waitlisted member to going status.
     *
     * Uses FIFO ordering (by waitlisted_at). Skips members whose
     * guest_count + 1 exceeds available spots.
     */
    public function promoteNext(Event $event): ?Rsvp
    {
        if ($event->status === EventStatus::Cancelled) {
            return null;
        }

        $availableSpots = $this->availableSpots($event);

        if ($availableSpots <= 0) {
            return null;
        }

        $waitlisted = $event->rsvps()
            ->where('status', RsvpStatus::Waitlisted)
            ->orderBy('waitlisted_at')
            ->get();

        if ($waitlisted->isEmpty()) {
            return null;
        }

        foreach ($waitlisted as $rsvp) {
            $spotsNeeded = 1 + $rsvp->guest_count;

            if ($spotsNeeded > $availableSpots) {
                continue;
            }

            $rsvp->update([
                'status' => RsvpStatus::Going,
                'waitlisted_at' => null,
            ]);

            $rsvp->user->notify(new PromotedFromWaitlist($event, $rsvp));

            return $rsvp;
        }

        return null;
    }

    /**
     * Promote all eligible waitlisted members when multiple spots open.
     *
     * Continues promoting until no more eligible members fit.
     * Revisits previously skipped members as spots free up from
     * promoting smaller parties.
     *
     * @return array<int, Rsvp>
     */
    public function promoteAll(Event $event): array
    {
        $promoted = [];

        do {
            $rsvp = $this->promoteNext($event);

            if ($rsvp !== null) {
                $promoted[] = $rsvp;
            }
        } while ($rsvp !== null);

        return $promoted;
    }

    /**
     * Calculate available spots for an event.
     */
    private function availableSpots(Event $event): int
    {
        if ($event->rsvp_limit === null) {
            return PHP_INT_MAX;
        }

        $takenSpots = $event->rsvps()
            ->where('status', RsvpStatus::Going)
            ->selectRaw('COALESCE(SUM(1 + guest_count), 0) as total')
            ->value('total');

        return max(0, $event->rsvp_limit - (int) $takenSpots);
    }
}
