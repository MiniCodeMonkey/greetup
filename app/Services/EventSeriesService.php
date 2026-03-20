<?php

namespace App\Services;

use App\Enums\EventStatus;
use App\Models\Event;
use App\Models\EventSeries;
use App\Models\Group;
use Carbon\Carbon;
use InvalidArgumentException;
use RRule\RRule;

class EventSeriesService
{
    /**
     * Default number of months to generate events ahead.
     */
    public const GENERATION_MONTHS = 3;

    /**
     * Create an event series and generate recurring event instances.
     *
     * @param  array<string, mixed>  $eventTemplate  Base event attributes to replicate
     */
    public function createSeries(Group $group, string $recurrenceRule, array $eventTemplate): EventSeries
    {
        $this->createRRule($recurrenceRule); // validates

        $series = EventSeries::create([
            'group_id' => $group->id,
            'recurrence_rule' => $recurrenceRule,
        ]);

        $this->generateInstances($series, $eventTemplate);

        return $series;
    }

    /**
     * Generate event instances for a series up to the generation horizon.
     *
     * @param  array<string, mixed>  $eventTemplate  Base event attributes to replicate
     * @return list<Event>
     */
    public function generateInstances(EventSeries $series, array $eventTemplate, ?Carbon $after = null): array
    {
        $after ??= now();
        $until = $after->copy()->addMonths(self::GENERATION_MONTHS);

        $rrule = $this->createRRule($series->recurrence_rule);

        $events = [];

        foreach ($rrule as $occurrence) {
            $occurrenceDate = Carbon::instance($occurrence);

            if ($occurrenceDate->lessThan($after)) {
                continue;
            }

            if ($occurrenceDate->greaterThan($until)) {
                break;
            }

            $existingEvent = Event::query()
                ->where('series_id', $series->id)
                ->where('starts_at', $occurrenceDate)
                ->exists();

            if ($existingEvent) {
                continue;
            }

            $duration = null;

            if (isset($eventTemplate['ends_at'], $eventTemplate['starts_at'])) {
                $templateStart = Carbon::parse($eventTemplate['starts_at']);
                $templateEnd = Carbon::parse($eventTemplate['ends_at']);
                $duration = $templateStart->diffInMinutes($templateEnd);
            }

            $attributes = array_merge($eventTemplate, [
                'group_id' => $series->group_id,
                'series_id' => $series->id,
                'starts_at' => $occurrenceDate,
                'status' => EventStatus::Published,
            ]);

            if ($duration !== null) {
                $attributes['ends_at'] = $occurrenceDate->copy()->addMinutes($duration);
            }

            unset($attributes['id'], $attributes['slug']);

            $events[] = Event::create($attributes);
        }

        return $events;
    }

    /**
     * Update a single event in a series (this event only).
     *
     * @param  array<string, mixed>  $attributes
     */
    public function updateSingle(Event $event, array $attributes): Event
    {
        if ($event->series_id === null) {
            throw new InvalidArgumentException('Event is not part of a series.');
        }

        $event->update($attributes);

        return $event->fresh();
    }

    /**
     * Update this event and all future events in the series.
     *
     * @param  array<string, mixed>  $attributes
     * @return list<Event>
     */
    public function updateAllFuture(Event $event, array $attributes): array
    {
        if ($event->series_id === null) {
            throw new InvalidArgumentException('Event is not part of a series.');
        }

        $futureEvents = Event::query()
            ->where('series_id', $event->series_id)
            ->where('starts_at', '>=', $event->starts_at)
            ->get();

        $updated = [];

        foreach ($futureEvents as $futureEvent) {
            $futureEvent->update($attributes);
            $updated[] = $futureEvent->fresh();
        }

        return $updated;
    }

    /**
     * Parse an RRULE string, extracting DTSTART if embedded.
     *
     * @return array{rule: string, dtstart: string|null}
     */
    private function parseRRule(string $recurrenceRule): array
    {
        $dtstart = null;
        $parts = [];

        foreach (explode(';', $recurrenceRule) as $part) {
            if (str_starts_with(strtoupper($part), 'DTSTART=')) {
                $dtstart = substr($part, 8);
            } else {
                $parts[] = $part;
            }
        }

        return [
            'rule' => implode(';', $parts),
            'dtstart' => $dtstart,
        ];
    }

    /**
     * Create an RRule instance from the stored recurrence rule string.
     */
    private function createRRule(string $recurrenceRule): RRule
    {
        $parsed = $this->parseRRule($recurrenceRule);

        $options = $parsed['rule'];

        if ($parsed['dtstart'] !== null) {
            $options = [
                'DTSTART' => $parsed['dtstart'],
                ...$this->parseRuleParts($parsed['rule']),
            ];
        }

        try {
            return new RRule($options);
        } catch (\Exception $e) {
            throw new InvalidArgumentException("Invalid recurrence rule: {$e->getMessage()}");
        }
    }

    /**
     * Parse RRULE parts into an associative array.
     *
     * @return array<string, string>
     */
    private function parseRuleParts(string $rule): array
    {
        $parts = [];

        foreach (explode(';', $rule) as $part) {
            if (str_contains($part, '=')) {
                [$key, $value] = explode('=', $part, 2);
                $parts[strtoupper($key)] = $value;
            }
        }

        return $parts;
    }
}
