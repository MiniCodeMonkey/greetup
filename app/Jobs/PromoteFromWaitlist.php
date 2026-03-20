<?php

namespace App\Jobs;

use App\Enums\RsvpStatus;
use App\Models\Event;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class PromoteFromWaitlist implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(public Event $event) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $availableSpots = $this->calculateAvailableSpots();

        if ($availableSpots <= 0) {
            return;
        }

        $waitlisted = $this->event->rsvps()
            ->where('status', RsvpStatus::Waitlisted)
            ->orderBy('waitlisted_at')
            ->get();

        foreach ($waitlisted as $rsvp) {
            $spotsNeeded = 1 + $rsvp->guest_count;

            if ($spotsNeeded > $availableSpots) {
                continue;
            }

            $rsvp->update([
                'status' => RsvpStatus::Going,
                'waitlisted_at' => null,
            ]);

            $availableSpots -= $spotsNeeded;

            if ($availableSpots <= 0) {
                break;
            }
        }
    }

    private function calculateAvailableSpots(): int
    {
        if ($this->event->rsvp_limit === null) {
            return PHP_INT_MAX;
        }

        $takenSpots = $this->event->rsvps()
            ->where('status', RsvpStatus::Going)
            ->selectRaw('COALESCE(SUM(1 + guest_count), 0) as total')
            ->value('total');

        return $this->event->rsvp_limit - (int) $takenSpots;
    }
}
