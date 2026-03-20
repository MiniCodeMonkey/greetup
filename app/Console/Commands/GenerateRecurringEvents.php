<?php

namespace App\Console\Commands;

use App\Enums\EventStatus;
use App\Models\Event;
use App\Models\EventSeries;
use Carbon\Carbon;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use RRule\RRule;

#[Signature('events:generate-recurring')]
#[Description('Generate next batch of recurring event instances from event series RRULE strings, up to 3 months ahead')]
class GenerateRecurringEvents extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $horizon = now()->addMonths(3);
        $created = 0;

        EventSeries::with(['events', 'group'])->each(function (EventSeries $series) use ($horizon, &$created): void {
            $latestEvent = $series->events()->orderByDesc('starts_at')->first();

            $futureCount = $series->events()
                ->where('starts_at', '>', now())
                ->whereIn('status', [EventStatus::Draft, EventStatus::Published])
                ->count();

            if ($futureCount >= 3) {
                return;
            }

            $rruleString = $series->recurrence_rule;

            $dtstart = $latestEvent?->starts_at ?? now();

            $rrule = new RRule(array_merge(
                RRule::parseRfcString("RRULE:{$rruleString}"),
                ['DTSTART' => $dtstart->toDateTime()]
            ));

            $needed = 3 - $futureCount;
            $generated = 0;

            foreach ($rrule as $occurrence) {
                $occurrenceDate = Carbon::instance($occurrence);

                if ($occurrenceDate->lte($dtstart)) {
                    continue;
                }

                if ($occurrenceDate->gt($horizon)) {
                    break;
                }

                $templateEvent = $latestEvent ?? $series->events()->orderByDesc('starts_at')->first();

                if (! $templateEvent) {
                    break;
                }

                $duration = $templateEvent->ends_at
                    ? $templateEvent->starts_at->diffInSeconds($templateEvent->ends_at)
                    : null;

                Event::create([
                    'group_id' => $series->group_id,
                    'created_by' => $templateEvent->created_by,
                    'name' => $templateEvent->name,
                    'description' => $templateEvent->description,
                    'description_html' => $templateEvent->description_html,
                    'event_type' => $templateEvent->event_type,
                    'status' => EventStatus::Published,
                    'starts_at' => $occurrenceDate,
                    'ends_at' => $duration ? $occurrenceDate->copy()->addSeconds($duration) : null,
                    'timezone' => $templateEvent->timezone,
                    'venue_name' => $templateEvent->venue_name,
                    'venue_address' => $templateEvent->venue_address,
                    'venue_latitude' => $templateEvent->venue_latitude,
                    'venue_longitude' => $templateEvent->venue_longitude,
                    'online_link' => $templateEvent->online_link,
                    'rsvp_limit' => $templateEvent->rsvp_limit,
                    'guest_limit' => $templateEvent->guest_limit,
                    'is_chat_enabled' => $templateEvent->is_chat_enabled,
                    'is_comments_enabled' => $templateEvent->is_comments_enabled,
                    'series_id' => $series->id,
                ]);

                $created++;
                $generated++;

                if ($generated >= $needed) {
                    break;
                }
            }
        });

        $this->info("Generated {$created} recurring event instance(s).");

        return self::SUCCESS;
    }
}
