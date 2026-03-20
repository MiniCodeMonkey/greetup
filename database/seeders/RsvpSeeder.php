<?php

namespace Database\Seeders;

use App\Enums\AttendanceMode;
use App\Enums\AttendanceResult;
use App\Enums\EventStatus;
use App\Enums\RsvpStatus;
use App\Models\Event;
use App\Models\Rsvp;
use Illuminate\Database\Seeder;

class RsvpSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $events = Event::with('group.members')->whereIn('status', [
            EventStatus::Published,
            EventStatus::Past,
        ])->get();

        $capacityEventsMarked = 0;

        foreach ($events as $event) {
            $members = $event->group->members->shuffle();
            if ($members->isEmpty()) {
                continue;
            }

            // Decide how many RSVPs — vary attendance levels
            $isPast = $event->status === EventStatus::Past;
            $memberCount = $members->count();
            $rsvpRatio = fake()->randomFloat(2, 0.3, 0.8);
            $targetRsvps = (int) ceil($memberCount * $rsvpRatio);

            // For capacity events, ensure we fill them
            $hasLimit = $event->rsvp_limit !== null;
            $shouldFillToCapacity = $hasLimit && $capacityEventsMarked < 2 && $targetRsvps >= ($event->rsvp_limit ?? 999);

            if ($shouldFillToCapacity) {
                $capacityEventsMarked++;
            }

            $goingCount = 0;
            $rsvpCount = 0;

            foreach ($members->take($targetRsvps) as $member) {
                $atCapacity = $hasLimit && $goingCount >= $event->rsvp_limit;

                // If at capacity and we want waitlisted members
                if ($atCapacity && $shouldFillToCapacity) {
                    Rsvp::create([
                        'event_id' => $event->id,
                        'user_id' => $member->id,
                        'status' => RsvpStatus::Waitlisted,
                        'guest_count' => 0,
                        'attendance_mode' => AttendanceMode::InPerson,
                        'waitlisted_at' => now()->subDays(rand(1, 7)),
                    ]);
                    $rsvpCount++;

                    continue;
                }

                // Regular going RSVP
                $guestCount = $event->guest_limit > 0 ? fake()->numberBetween(0, $event->guest_limit) : 0;

                $rsvpData = [
                    'event_id' => $event->id,
                    'user_id' => $member->id,
                    'status' => RsvpStatus::Going,
                    'guest_count' => $guestCount,
                    'attendance_mode' => AttendanceMode::InPerson,
                ];

                // For past events, mark ~80% as attended, ~20% as no-show
                if ($isPast) {
                    $attended = fake()->boolean(80);
                    $rsvpData['attended'] = $attended ? AttendanceResult::Attended : AttendanceResult::NoShow;
                    if ($attended) {
                        $rsvpData['checked_in'] = true;
                        $rsvpData['checked_in_at'] = $event->starts_at;
                    }
                }

                Rsvp::create($rsvpData);
                $goingCount++;
                $rsvpCount++;
            }

            // Add a few NotGoing RSVPs for realism
            $notGoingMembers = $members->skip($targetRsvps)->take(rand(1, 3));
            foreach ($notGoingMembers as $member) {
                Rsvp::create([
                    'event_id' => $event->id,
                    'user_id' => $member->id,
                    'status' => RsvpStatus::NotGoing,
                    'guest_count' => 0,
                ]);
            }
        }

        // Ensure at least 2 events have waitlisted members if they don't already
        $eventsWithWaitlist = Rsvp::where('status', RsvpStatus::Waitlisted)->distinct('event_id')->count('event_id');
        if ($eventsWithWaitlist < 2) {
            $limitedEvents = Event::whereNotNull('rsvp_limit')
                ->where('status', EventStatus::Published)
                ->limit(2 - $eventsWithWaitlist)
                ->get();

            foreach ($limitedEvents as $event) {
                $members = $event->group->members()
                    ->whereNotIn('users.id', $event->rsvps()->pluck('user_id'))
                    ->limit(3)
                    ->get();

                foreach ($members as $member) {
                    Rsvp::create([
                        'event_id' => $event->id,
                        'user_id' => $member->id,
                        'status' => RsvpStatus::Waitlisted,
                        'guest_count' => 0,
                        'attendance_mode' => AttendanceMode::InPerson,
                        'waitlisted_at' => now()->subDays(rand(1, 5)),
                    ]);
                }
            }
        }
    }
}
