<?php

namespace App\Http\Controllers\Events;

use App\Enums\EventStatus;
use App\Enums\EventType;
use App\Enums\RsvpStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Events\CreateEventRequest;
use App\Http\Requests\Events\UpdateEventRequest;
use App\Models\Event;
use App\Models\Group;
use App\Models\Setting;
use App\Notifications\EventCancelled;
use App\Notifications\EventUpdated;
use App\Notifications\NewEvent;
use App\Services\EventSeriesService;
use App\Services\MarkdownService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EventController extends Controller
{
    /**
     * Display the event page.
     */
    public function show(Request $request, Group $group, Event $event): View
    {
        abort_unless($event->group_id === $group->id, 404);

        $event->load(['hosts', 'group']);

        $goingCount = $event->rsvps()->where('status', RsvpStatus::Going)->count();
        $waitlistCount = $event->rsvps()->where('status', RsvpStatus::Waitlisted)->count();

        $attendees = $event->rsvps()
            ->where('status', RsvpStatus::Going)
            ->with('user')
            ->limit(5)
            ->get()
            ->pluck('user');

        $tab = $request->query('tab', 'details');

        $coverPhoto = $event->getFirstMediaUrl('cover_photo', 'header');
        if (! $coverPhoto) {
            $coverPhoto = $group->getFirstMediaUrl('cover_photo', 'header');
        }

        $seoTitle = $event->name.' · '.$group->name.' — '.Setting::get('site_name', config('app.name', 'Greetup'));
        $seoDescription = $event->description
            ? Str::limit(strip_tags($event->description), 160)
            : 'Event hosted by '.$group->name;
        $seoImage = $coverPhoto ?: null;

        $jsonLd = $this->buildJsonLd($event, $group, $coverPhoto, $goingCount);

        $userTimezone = null;
        if ($request->user() && $request->user()->timezone && $request->user()->timezone !== $event->timezone) {
            $userTimezone = $request->user()->timezone;
        }

        return view('events.show', [
            'event' => $event,
            'group' => $group,
            'goingCount' => $goingCount,
            'waitlistCount' => $waitlistCount,
            'attendees' => $attendees,
            'tab' => $tab,
            'coverPhoto' => $coverPhoto,
            'seoTitle' => $seoTitle,
            'seoDescription' => $seoDescription,
            'seoImage' => $seoImage,
            'jsonLd' => $jsonLd,
            'userTimezone' => $userTimezone,
        ]);
    }

    /**
     * Download an .ics calendar file for the event.
     */
    public function calendar(Group $group, Event $event): StreamedResponse
    {
        abort_unless($event->group_id === $group->id, 404);

        $dtStart = $event->starts_at->utc()->format('Ymd\THis\Z');
        $dtEnd = $event->ends_at
            ? $event->ends_at->utc()->format('Ymd\THis\Z')
            : $event->starts_at->utc()->addHours(2)->format('Ymd\THis\Z');
        $now = now()->utc()->format('Ymd\THis\Z');
        $uid = $event->id.'@'.config('app.url');
        $summary = $this->escapeIcs($event->name);
        $description = $this->escapeIcs(strip_tags($event->description ?? ''));
        $organizer = $this->escapeIcs($event->group->name);

        $location = match ($event->event_type) {
            EventType::Online => $this->escapeIcs($event->online_link ?? ''),
            default => $this->escapeIcs($event->venue_address ?? ($event->venue_name ?? '')),
        };

        $ics = "BEGIN:VCALENDAR\r\nVERSION:2.0\r\nPRODID:-//Greetup//Event//EN\r\nBEGIN:VEVENT\r\nUID:{$uid}\r\nDTSTAMP:{$now}\r\nDTSTART:{$dtStart}\r\nDTEND:{$dtEnd}\r\nSUMMARY:{$summary}\r\nDESCRIPTION:{$description}\r\nLOCATION:{$location}\r\nORGANIZER:{$organizer}\r\nEND:VEVENT\r\nEND:VCALENDAR";

        $filename = Str::slug($event->name).'.ics';

        return response()->streamDownload(function () use ($ics) {
            echo $ics;
        }, $filename, [
            'Content-Type' => 'text/calendar; charset=utf-8',
        ]);
    }

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

        $cutoff = $event->ends_at
            ? $event->ends_at->copy()->addHours(24)
            : $event->starts_at->copy()->addHours(24);

        abort_if(now()->gt($cutoff), 403, 'The editing window for this event has closed.');

        return view('events.edit', [
            'group' => $group,
            'event' => $event,
        ]);
    }

    /**
     * Update an event (handles both single and series edits).
     */
    public function update(UpdateEventRequest $request, Group $group, Event $event, MarkdownService $markdownService, EventSeriesService $seriesService): RedirectResponse
    {
        $validated = $request->validated();

        $timezone = $validated['timezone'] ?? $event->timezone ?? $group->timezone ?? config('app.timezone');

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

        $attributes = [
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'description_html' => isset($validated['description'])
                ? $markdownService->render($validated['description'])
                : null,
            'event_type' => $validated['event_type'],
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'venue_name' => $validated['venue_name'] ?? null,
            'venue_address' => $validated['venue_address'] ?? null,
            'online_link' => $validated['online_link'] ?? null,
            'rsvp_limit' => $validated['rsvp_limit'] ?? null,
            'guest_limit' => $validated['guest_limit'] ?? 0,
            'rsvp_opens_at' => $rsvpOpensAt,
            'rsvp_closes_at' => $rsvpClosesAt,
            'is_chat_enabled' => $validated['is_chat_enabled'] ?? true,
            'is_comments_enabled' => $validated['is_comments_enabled'] ?? true,
            'timezone' => $timezone,
        ];

        $editScope = $validated['edit_scope'] ?? 'single';

        if ($event->series_id !== null && $editScope === 'all_future') {
            $seriesService->updateAllFuture($event, $attributes);
            $message = 'This and all future events updated.';
        } else {
            $event->update($attributes);
            $message = 'Event updated successfully.';
        }

        if ($request->hasFile('cover_photo')) {
            $event->addMediaFromRequest('cover_photo')
                ->toMediaCollection('cover_photo');
        }

        $event->refresh();

        if ($event->status === EventStatus::Published) {
            $rsvpUsers = $event->rsvps()
                ->whereIn('status', [RsvpStatus::Going, RsvpStatus::Waitlisted])
                ->with('user')
                ->get()
                ->pluck('user');

            foreach ($rsvpUsers as $rsvpUser) {
                $rsvpUser->notify(new EventUpdated($event, $group));
            }
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

                $this->notifyCancelledEventAttendees($futureEvent, $group);
            }

            $message = $futureEvents->count().' events cancelled.';
        } else {
            $event->update([
                'status' => EventStatus::Cancelled,
                'cancelled_at' => now(),
                'cancellation_reason' => $request->input('cancellation_reason'),
            ]);

            $this->notifyCancelledEventAttendees($event, $group);

            $message = 'Event cancelled.';
        }

        return redirect()->route('groups.show', $group)
            ->with('status', $message);
    }

    /**
     * Notify Going/Waitlisted attendees that an event has been cancelled.
     */
    private function notifyCancelledEventAttendees(Event $event, Group $group): void
    {
        $attendees = $event->rsvps()
            ->whereIn('status', [RsvpStatus::Going, RsvpStatus::Waitlisted])
            ->with('user')
            ->get()
            ->pluck('user');

        foreach ($attendees as $user) {
            $user->notify(new EventCancelled($event, $group));
        }
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

    /**
     * Build JSON-LD structured data for the event.
     *
     * @return array<string, mixed>
     */
    private function buildJsonLd(Event $event, Group $group, string $coverPhoto, int $goingCount): array
    {
        $attendanceMode = match ($event->event_type) {
            EventType::InPerson => 'https://schema.org/OfflineEventAttendanceMode',
            EventType::Online => 'https://schema.org/OnlineEventAttendanceMode',
            EventType::Hybrid => 'https://schema.org/MixedEventAttendanceMode',
        };

        $eventStatus = $event->status === EventStatus::Cancelled
            ? 'https://schema.org/EventCancelled'
            : 'https://schema.org/EventScheduled';

        $location = match (true) {
            $event->event_type === EventType::Online => [
                '@type' => 'VirtualLocation',
                'url' => $event->online_link ?? '',
            ],
            $event->event_type === EventType::Hybrid => [
                [
                    '@type' => 'Place',
                    'name' => $event->venue_name ?? 'Venue',
                    'address' => $event->venue_address ?? '',
                ],
                [
                    '@type' => 'VirtualLocation',
                    'url' => $event->online_link ?? '',
                ],
            ],
            default => [
                '@type' => 'Place',
                'name' => $event->venue_name ?? 'Venue',
                'address' => $event->venue_address ?? '',
            ],
        };

        $availability = 'https://schema.org/InStock';
        if ($event->rsvp_opens_at && $event->rsvp_opens_at->isFuture()) {
            $availability = 'https://schema.org/PreOrder';
        } elseif ($event->rsvp_limit && $goingCount >= $event->rsvp_limit) {
            $availability = 'https://schema.org/SoldOut';
        }

        $jsonLd = [
            '@context' => 'https://schema.org',
            '@type' => 'Event',
            'name' => $event->name,
            'description' => Str::limit(strip_tags($event->description ?? ''), 300),
            'startDate' => $event->starts_at->toIso8601String(),
            'eventStatus' => $eventStatus,
            'eventAttendanceMode' => $attendanceMode,
            'location' => $location,
            'organizer' => [
                '@type' => 'Organization',
                'name' => $group->name,
                'url' => route('groups.show', $group),
            ],
            'image' => $coverPhoto ?: asset('images/og-default.png'),
            'offers' => [
                '@type' => 'Offer',
                'price' => '0',
                'priceCurrency' => 'USD',
                'availability' => $availability,
            ],
        ];

        if ($event->ends_at) {
            $jsonLd['endDate'] = $event->ends_at->toIso8601String();
        }

        return $jsonLd;
    }

    /**
     * Escape a string for use in an ICS file.
     */
    private function escapeIcs(string $text): string
    {
        return str_replace(["\n", ',', ';', '\\'], ['\\n', '\\,', '\\;', '\\\\'], $text);
    }
}
