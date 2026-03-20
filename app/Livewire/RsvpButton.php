<?php

namespace App\Livewire;

use App\Enums\AttendanceMode;
use App\Enums\EventStatus;
use App\Enums\EventType;
use App\Enums\RsvpStatus;
use App\Models\Event;
use App\Models\Rsvp;
use App\Notifications\RsvpConfirmation;
use App\Services\RsvpService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use InvalidArgumentException;
use Livewire\Component;

class RsvpButton extends Component
{
    public Event $event;

    public int $guestCount = 0;

    public ?string $attendanceMode = null;

    public ?string $currentStatus = null;

    public ?string $errorMessage = null;

    public function mount(Event $event): void
    {
        $this->event = $event;
        $this->loadCurrentRsvp();
    }

    public function rsvpGoing(RsvpService $rsvpService): void
    {
        $this->errorMessage = null;

        $user = Auth::user();

        if (! $user) {
            return;
        }

        try {
            $mode = $this->attendanceMode
                ? AttendanceMode::from($this->attendanceMode)
                : null;

            $rsvp = $rsvpService->rsvpGoing(
                $this->event,
                $user,
                $this->guestCount,
                $mode
            );

            $this->currentStatus = $rsvp->status->value;

            $user->notify(new RsvpConfirmation($this->event, $rsvp));
        } catch (InvalidArgumentException $e) {
            $this->errorMessage = $e->getMessage();
        }
    }

    public function rsvpNotGoing(RsvpService $rsvpService): void
    {
        $this->errorMessage = null;

        $user = Auth::user();

        if (! $user) {
            return;
        }

        $rsvp = $rsvpService->rsvpNotGoing($this->event, $user);

        $this->currentStatus = $rsvp->status->value;
        $this->guestCount = 0;
        $this->attendanceMode = null;
    }

    public function render(): View
    {
        $canRsvp = $this->canRsvp();
        $isFull = $this->isFull();

        return view('livewire.rsvp-button', [
            'canRsvp' => $canRsvp,
            'isFull' => $isFull,
            'isHybrid' => $this->event->event_type === EventType::Hybrid,
            'maxGuests' => $this->event->guest_limit,
        ]);
    }

    private function loadCurrentRsvp(): void
    {
        $user = Auth::user();

        if (! $user) {
            return;
        }

        $rsvp = Rsvp::where('event_id', $this->event->id)
            ->where('user_id', $user->id)
            ->first();

        if ($rsvp) {
            $this->currentStatus = $rsvp->status->value;
            $this->guestCount = $rsvp->guest_count;
            $this->attendanceMode = $rsvp->attendance_mode?->value;
        }
    }

    private function canRsvp(): bool
    {
        $user = Auth::user();

        if (! $user) {
            return false;
        }

        if (! $user->hasVerifiedEmail()) {
            return false;
        }

        if ($this->event->status !== EventStatus::Published) {
            return false;
        }

        if ($this->event->cancelled_at !== null) {
            return false;
        }

        // Check if event is past
        if ($this->event->ends_at !== null && $this->event->ends_at->isPast()) {
            return false;
        }

        if ($this->event->ends_at === null && $this->event->starts_at->isPast()) {
            return false;
        }

        // Check RSVP window
        if ($this->event->rsvp_opens_at !== null && $this->event->rsvp_opens_at->isFuture()) {
            return false;
        }

        if ($this->event->rsvp_closes_at !== null && $this->event->rsvp_closes_at->isPast()) {
            return false;
        }

        // Check group membership
        return $this->event->group->members()->where('user_id', $user->id)->exists();
    }

    private function isFull(): bool
    {
        if ($this->event->rsvp_limit === null) {
            return false;
        }

        $takenSpots = $this->event->rsvps()
            ->where('status', RsvpStatus::Going)
            ->selectRaw('COALESCE(SUM(1 + guest_count), 0) as total')
            ->value('total');

        return (int) $takenSpots >= $this->event->rsvp_limit;
    }
}
