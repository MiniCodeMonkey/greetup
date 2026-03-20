<?php

namespace App\Livewire;

use App\Enums\AttendanceResult;
use App\Enums\RsvpStatus;
use App\Models\Event;
use App\Models\Rsvp;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Livewire\WithPagination;

class AttendeeManager extends Component
{
    use WithPagination;

    public Event $event;

    public string $tab = 'going';

    public function mount(Event $event): void
    {
        $this->event = $event;
    }

    public function setTab(string $tab): void
    {
        if (in_array($tab, ['going', 'waitlisted', 'not_going'])) {
            $this->tab = $tab;
            $this->resetPage();
        }
    }

    public function changeStatus(int $rsvpId, string $newStatus): void
    {
        Gate::authorize('manageAttendees', $this->event);

        $rsvp = Rsvp::where('id', $rsvpId)
            ->where('event_id', $this->event->id)
            ->firstOrFail();

        $status = RsvpStatus::from($newStatus);

        $rsvp->update([
            'status' => $status,
            'waitlisted_at' => $status === RsvpStatus::Waitlisted ? now() : $rsvp->waitlisted_at,
        ]);
    }

    public function moveToGoing(int $rsvpId): void
    {
        Gate::authorize('manageAttendees', $this->event);

        $rsvp = Rsvp::where('id', $rsvpId)
            ->where('event_id', $this->event->id)
            ->firstOrFail();

        $rsvp->update([
            'status' => RsvpStatus::Going,
            'waitlisted_at' => null,
        ]);
    }

    public function removeRsvp(int $rsvpId): void
    {
        Gate::authorize('manageAttendees', $this->event);

        Rsvp::where('id', $rsvpId)
            ->where('event_id', $this->event->id)
            ->delete();
    }

    public function checkIn(int $rsvpId): void
    {
        Gate::authorize('checkIn', $this->event);

        $rsvp = Rsvp::where('id', $rsvpId)
            ->where('event_id', $this->event->id)
            ->firstOrFail();

        $rsvp->update([
            'checked_in' => true,
            'checked_in_at' => now(),
            'checked_in_by' => Auth::id(),
        ]);
    }

    public function markAttendance(int $rsvpId, string $result): void
    {
        Gate::authorize('manageAttendees', $this->event);

        $rsvp = Rsvp::where('id', $rsvpId)
            ->where('event_id', $this->event->id)
            ->firstOrFail();

        $rsvp->update([
            'attended' => AttendanceResult::from($result),
        ]);
    }

    public function render(): View
    {
        $status = match ($this->tab) {
            'going' => RsvpStatus::Going,
            'waitlisted' => RsvpStatus::Waitlisted,
            'not_going' => RsvpStatus::NotGoing,
            default => RsvpStatus::Going,
        };

        $rsvps = $this->event->rsvps()
            ->with('user')
            ->where('status', $status)
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        $isPast = $this->event->starts_at->isPast();

        $counts = [
            'going' => $this->event->rsvps()->where('status', RsvpStatus::Going)->count(),
            'waitlisted' => $this->event->rsvps()->where('status', RsvpStatus::Waitlisted)->count(),
            'not_going' => $this->event->rsvps()->where('status', RsvpStatus::NotGoing)->count(),
        ];

        return view('livewire.attendee-manager', [
            'rsvps' => $rsvps,
            'isPast' => $isPast,
            'counts' => $counts,
        ]);
    }
}
