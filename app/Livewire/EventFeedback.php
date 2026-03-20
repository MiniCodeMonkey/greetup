<?php

namespace App\Livewire;

use App\Enums\GroupRole;
use App\Models\Event;
use App\Models\Feedback;
use App\Models\User;
use App\Notifications\NewEventFeedback;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

class EventFeedback extends Component
{
    public Event $event;

    public int $rating = 0;

    public string $body = '';

    public function mount(Event $event): void
    {
        $this->event = $event;
    }

    public function submitFeedback(): void
    {
        Gate::authorize('create', [Feedback::class, $this->event]);

        $this->validate([
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'body' => ['nullable', 'string', 'max:5000'],
        ]);

        $feedback = Feedback::create([
            'event_id' => $this->event->id,
            'user_id' => Auth::id(),
            'rating' => $this->rating,
            'body' => $this->body ?: null,
        ]);

        $this->rating = 0;
        $this->body = '';

        $this->notifyFeedback($feedback);
    }

    public function render(): View
    {
        $user = Auth::user();
        $canSubmit = $user && Gate::allows('create', [Feedback::class, $this->event]);
        $canViewAttribution = $user && Gate::allows('viewAttribution', [Feedback::class, $this->event]);

        $feedbackQuery = $this->event->feedback()->with('user')->latest();
        $feedbackItems = $canViewAttribution ? $feedbackQuery->get() : collect();

        $averageRating = $this->event->feedback()->avg('rating');
        $feedbackCount = $this->event->feedback()->count();

        $userFeedback = $user
            ? $this->event->feedback()->where('user_id', $user->id)->first()
            : null;

        return view('livewire.event-feedback', [
            'canSubmit' => $canSubmit,
            'canViewAttribution' => $canViewAttribution,
            'feedbackItems' => $feedbackItems,
            'averageRating' => $averageRating ? round($averageRating, 1) : null,
            'feedbackCount' => $feedbackCount,
            'userFeedback' => $userFeedback,
        ]);
    }

    private function notifyFeedback(Feedback $feedback): void
    {
        $feedback->load('user');

        $hostIds = $this->event->hosts()->pluck('users.id')->toArray();

        $organizerIds = $this->event->group->members()
            ->wherePivot('is_banned', false)
            ->wherePivot('role', GroupRole::Organizer->value)
            ->pluck('users.id')
            ->toArray();

        $recipientIds = collect(array_merge($hostIds, $organizerIds))
            ->unique()
            ->reject(fn (int $id): bool => $id === $feedback->user_id)
            ->values();

        $recipients = User::whereIn('id', $recipientIds)->get();

        foreach ($recipients as $recipient) {
            $recipient->notify(new NewEventFeedback($feedback, $this->event));
        }
    }
}
