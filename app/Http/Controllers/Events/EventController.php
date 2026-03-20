<?php

namespace App\Http\Controllers\Events;

use App\Enums\EventStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Events\CreateEventRequest;
use App\Models\Event;
use App\Models\Group;
use App\Notifications\NewEvent;
use App\Services\EventSeriesService;
use App\Services\MarkdownService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class EventController extends Controller
{
    /**
     * Show the event creation form.
     */
    public function create(Group $group): View
    {
        return view('events.create', [
            'group' => $group,
        ]);
    }

    /**
     * Store a newly created event.
     */
    public function store(CreateEventRequest $request, Group $group, MarkdownService $markdownService, EventSeriesService $seriesService): RedirectResponse
    {
        $validated = $request->validated();

        $timezone = $validated['timezone'] ?? $group->timezone ?? config('app.timezone');
        $status = ($validated['status'] ?? 'draft') === 'published'
            ? EventStatus::Published
            : EventStatus::Draft;

        $startsAt = Carbon::parse($validated['starts_at'], $timezone)->utc();
        $endsAt = isset($validated['ends_at'])
            ? Carbon::parse($validated['ends_at'], $timezone)->utc()
            : null;
        $rsvpOpensAt = isset($validated['rsvp_opens_at'])
            ? Carbon::parse($validated['rsvp_opens_at'], $timezone)->utc()
            : null;
        $rsvpClosesAt = isset($validated['rsvp_closes_at'])
            ? Carbon::parse($validated['rsvp_closes_at'], $timezone)->utc()
            : null;

        $eventAttributes = [
            'group_id' => $group->id,
            'created_by' => $request->user()->id,
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'description_html' => isset($validated['description'])
                ? $markdownService->render($validated['description'])
                : null,
            'event_type' => $validated['event_type'],
            'status' => $status,
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'timezone' => $timezone,
            'venue_name' => $validated['venue_name'] ?? null,
            'venue_address' => $validated['venue_address'] ?? null,
            'online_link' => $validated['online_link'] ?? null,
            'rsvp_limit' => $validated['rsvp_limit'] ?? null,
            'guest_limit' => $validated['guest_limit'] ?? 0,
            'rsvp_opens_at' => $rsvpOpensAt,
            'rsvp_closes_at' => $rsvpClosesAt,
            'is_chat_enabled' => $validated['is_chat_enabled'] ?? true,
            'is_comments_enabled' => $validated['is_comments_enabled'] ?? true,
        ];

        $isRecurring = (bool) ($validated['is_recurring'] ?? false);

        if ($isRecurring) {
            $rrule = $this->buildRRule($validated, $startsAt);
            $series = $seriesService->createSeries($group, $rrule, $eventAttributes);
            $events = $series->events;

            foreach ($events as $seriesEvent) {
                $seriesEvent->hosts()->attach($request->user()->id);
            }

            return redirect()->route('groups.show', $group)
                ->with('status', 'Recurring event series created with '.$events->count().' instances!');
        }

        $event = Event::create($eventAttributes);

        if ($request->hasFile('cover_photo')) {
            $event->addMediaFromRequest('cover_photo')
                ->toMediaCollection('cover_photo');
        }

        $event->hosts()->attach($request->user()->id);

        if ($status === EventStatus::Published) {
            $members = $group->members()
                ->where('group_members.is_banned', false)
                ->where('user_id', '!=', $request->user()->id)
                ->get();

            foreach ($members as $member) {
                $member->notify(new NewEvent($event, $group));
            }
        }

        return redirect()->route('groups.show', $group)
            ->with('status', 'Event created successfully!');
    }

    /**
     * Show the event edit form.
     */
    public function edit(Group $group, Event $event): View
    {
        Gate::authorize('update', $event);

        return view('events.edit', [
            'group' => $group,
            'event' => $event,
        ]);
    }

    /**
     * Update an event (handles both single and series edits).
     */
    public function update(Request $request, Group $group, Event $event, MarkdownService $markdownService, EventSeriesService $seriesService): RedirectResponse
    {
        Gate::authorize('update', $event);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:10000'],
            'venue_name' => ['nullable', 'string', 'max:255'],
            'venue_address' => ['nullable', 'string', 'max:500'],
            'online_link' => ['nullable', 'url', 'max:2000'],
            'edit_scope' => ['nullable', 'in:single,all_future'],
        ]);

        $attributes = [
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'description_html' => isset($validated['description'])
                ? $markdownService->render($validated['description'])
                : null,
            'venue_name' => $validated['venue_name'] ?? null,
            'venue_address' => $validated['venue_address'] ?? null,
            'online_link' => $validated['online_link'] ?? null,
        ];

        $editScope = $validated['edit_scope'] ?? 'single';

        if ($event->series_id !== null && $editScope === 'all_future') {
            $seriesService->updateAllFuture($event, $attributes);
            $message = 'This and all future events updated.';
        } else {
            $event->update($attributes);
            $message = 'Event updated successfully.';
        }

        return redirect()->route('groups.show', $group)
            ->with('status', $message);
    }

    /**
     * Cancel an event (handles both single and series cancellations).
     */
    public function cancel(Request $request, Group $group, Event $event): RedirectResponse
    {
        Gate::authorize('cancel', $event);

        $cancelScope = $request->input('cancel_scope', 'single');

        if ($event->series_id !== null && $cancelScope === 'all_future') {
            $futureEvents = Event::query()
                ->where('series_id', $event->series_id)
                ->where('starts_at', '>=', $event->starts_at)
                ->where('status', '!=', EventStatus::Cancelled)
                ->get();

            foreach ($futureEvents as $futureEvent) {
                $futureEvent->update([
                    'status' => EventStatus::Cancelled,
                    'cancelled_at' => now(),
                    'cancellation_reason' => $request->input('cancellation_reason'),
                ]);
            }

            $message = $futureEvents->count().' events cancelled.';
        } else {
            $event->update([
                'status' => EventStatus::Cancelled,
                'cancelled_at' => now(),
                'cancellation_reason' => $request->input('cancellation_reason'),
            ]);

            $message = 'Event cancelled.';
        }

        return redirect()->route('groups.show', $group)
            ->with('status', $message);
    }

    /**
     * Build an RRULE string from the validated recurrence form input.
     */
    private function buildRRule(array $validated, Carbon $startsAt): string
    {
        $pattern = $validated['recurrence_pattern'];

        if ($pattern === 'custom') {
            return $validated['custom_rrule'];
        }

        $dayOfWeek = strtoupper(substr($startsAt->format('l'), 0, 2));
        $dtstart = 'DTSTART='.$startsAt->format('Ymd\THis\Z');

        return match ($pattern) {
            'weekly' => $dtstart.';FREQ=WEEKLY;BYDAY='.$dayOfWeek,
            'biweekly' => $dtstart.';FREQ=WEEKLY;INTERVAL=2;BYDAY='.$dayOfWeek,
            'monthly' => $dtstart.';FREQ=MONTHLY;BYDAY='.$this->monthlyByDay($startsAt),
            default => throw new \InvalidArgumentException("Unknown recurrence pattern: {$pattern}"),
        };
    }

    /**
     * Compute the BYDAY value for monthly recurrence (e.g., "1MO" for first Monday).
     */
    private function monthlyByDay(Carbon $date): string
    {
        $weekOfMonth = (int) ceil($date->day / 7);
        $dayOfWeek = strtoupper(substr($date->format('l'), 0, 2));

        return $weekOfMonth.$dayOfWeek;
    }
}
