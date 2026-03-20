<?php

namespace App\Services;

use App\Enums\AttendanceMode;
use App\Enums\EventStatus;
use App\Enums\EventType;
use App\Enums\RsvpStatus;
use App\Jobs\PromoteFromWaitlist;
use App\Models\Event;
use App\Models\Rsvp;
use App\Models\User;
use InvalidArgumentException;

class RsvpService
{
    /**
     * RSVP a user as going to an event.
     */
    public function rsvpGoing(Event $event, User $user, int $guestCount = 0, ?AttendanceMode $attendanceMode = null): Rsvp
    {
        $this->validateCanRsvp($event, $user);
        $this->validateGuestCount($event, $guestCount);
        $this->validateAttendanceMode($event, $attendanceMode);

        $spotsNeeded = 1 + $guestCount;
        $availableSpots = $this->availableSpots($event);

        if ($availableSpots !== null && $spotsNeeded > $availableSpots) {
            return $this->autoWaitlist($event, $user, $guestCount, $attendanceMode);
        }

        return Rsvp::updateOrCreate(
            ['event_id' => $event->id, 'user_id' => $user->id],
            [
                'status' => RsvpStatus::Going,
                'guest_count' => $guestCount,
                'attendance_mode' => $attendanceMode,
                'waitlisted_at' => null,
            ]
        );
    }

    /**
     * RSVP a user as not going to an event.
     */
    public function rsvpNotGoing(Event $event, User $user): Rsvp
    {
        $rsvp = Rsvp::where('event_id', $event->id)
            ->where('user_id', $user->id)
            ->first();

        $wasGoing = $rsvp?->status === RsvpStatus::Going;

        $rsvp = Rsvp::updateOrCreate(
            ['event_id' => $event->id, 'user_id' => $user->id],
            [
                'status' => RsvpStatus::NotGoing,
                'guest_count' => 0,
                'waitlisted_at' => null,
            ]
        );

        if ($wasGoing) {
            PromoteFromWaitlist::dispatch($event);
        }

        return $rsvp;
    }

    /**
     * Join the waitlist for an event.
     */
    public function joinWaitlist(Event $event, User $user, int $guestCount = 0): Rsvp
    {
        $this->validateCanRsvp($event, $user);
        $this->validateGuestCount($event, $guestCount);

        return Rsvp::updateOrCreate(
            ['event_id' => $event->id, 'user_id' => $user->id],
            [
                'status' => RsvpStatus::Waitlisted,
                'guest_count' => $guestCount,
                'waitlisted_at' => now(),
            ]
        );
    }

    /**
     * Auto-waitlist when event is full.
     */
    private function autoWaitlist(Event $event, User $user, int $guestCount, ?AttendanceMode $attendanceMode): Rsvp
    {
        return Rsvp::updateOrCreate(
            ['event_id' => $event->id, 'user_id' => $user->id],
            [
                'status' => RsvpStatus::Waitlisted,
                'guest_count' => $guestCount,
                'attendance_mode' => $attendanceMode,
                'waitlisted_at' => now(),
            ]
        );
    }

    /**
     * Calculate available spots for an event.
     */
    private function availableSpots(Event $event): ?int
    {
        if ($event->rsvp_limit === null) {
            return null;
        }

        $takenSpots = $event->rsvps()
            ->where('status', RsvpStatus::Going)
            ->selectRaw('COALESCE(SUM(1 + guest_count), 0) as total')
            ->value('total');

        return max(0, $event->rsvp_limit - (int) $takenSpots);
    }

    /**
     * Validate that the user can RSVP to the event.
     */
    private function validateCanRsvp(Event $event, User $user): void
    {
        // Check group membership
        if (! $event->group->members()->where('user_id', $user->id)->exists()) {
            throw new InvalidArgumentException('User must be a member of the group to RSVP.');
        }

        // Check event is published
        if ($event->status !== EventStatus::Published) {
            throw new InvalidArgumentException('Event must be published to RSVP.');
        }

        // Check event is not cancelled
        if ($event->cancelled_at !== null) {
            throw new InvalidArgumentException('Cannot RSVP to a cancelled event.');
        }

        // Check event is not past
        if ($event->ends_at !== null && $event->ends_at->isPast()) {
            throw new InvalidArgumentException('Cannot RSVP to a past event.');
        }

        if ($event->ends_at === null && $event->starts_at->isPast()) {
            throw new InvalidArgumentException('Cannot RSVP to a past event.');
        }

        // Check RSVP window
        if ($event->rsvp_opens_at !== null && $event->rsvp_opens_at->isFuture()) {
            throw new InvalidArgumentException('RSVP window has not opened yet.');
        }

        if ($event->rsvp_closes_at !== null && $event->rsvp_closes_at->isPast()) {
            throw new InvalidArgumentException('RSVP window has closed.');
        }
    }

    /**
     * Validate the guest count.
     */
    private function validateGuestCount(Event $event, int $guestCount): void
    {
        if ($guestCount < 0) {
            throw new InvalidArgumentException('Guest count cannot be negative.');
        }

        if ($event->guest_limit !== null && $guestCount > $event->guest_limit) {
            throw new InvalidArgumentException("Guest count exceeds the event's guest limit of {$event->guest_limit}.");
        }
    }

    /**
     * Validate attendance mode for hybrid events.
     */
    private function validateAttendanceMode(Event $event, ?AttendanceMode $attendanceMode): void
    {
        if ($event->event_type === EventType::Hybrid && $attendanceMode === null) {
            throw new InvalidArgumentException('Attendance mode is required for hybrid events.');
        }
    }
}
