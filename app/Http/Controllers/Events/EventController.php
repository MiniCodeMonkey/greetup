<?php

namespace App\Http\Controllers\Events;

use App\Enums\EventStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Events\CreateEventRequest;
use App\Models\Event;
use App\Models\Group;
use App\Notifications\NewEvent;
use App\Services\MarkdownService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
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
    public function store(CreateEventRequest $request, Group $group, MarkdownService $markdownService): RedirectResponse
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

        $event = Event::create([
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
        ]);

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
}
