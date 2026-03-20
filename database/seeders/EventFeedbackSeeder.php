<?php

namespace Database\Seeders;

use App\Enums\AttendanceResult;
use App\Enums\EventStatus;
use App\Models\Event;
use App\Models\Feedback;
use Illuminate\Database\Seeder;

class EventFeedbackSeeder extends Seeder
{
    /**
     * Feedback text samples.
     *
     * @var list<string>
     */
    private const array FEEDBACK_TEXTS = [
        'Great event! Really enjoyed the discussion.',
        'Well organised, looking forward to the next one.',
        'The venue was perfect for this kind of event.',
        'Learned a lot, thank you to the organisers!',
        'Would have liked more time for Q&A.',
        'Excellent speakers and great networking opportunities.',
        'A bit crowded but overall a fantastic experience.',
        'The topic was very relevant. Please do more like this!',
        'Good event but started a bit late.',
        'Amazing community — everyone was so welcoming.',
        'The food was a nice touch!',
        'Could have been better organised, but the content was solid.',
        'Perfect way to spend a weekday evening.',
        'Really inspiring talks. Left feeling motivated!',
        'Nice mix of experienced and new members.',
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $pastEvents = Event::with(['rsvps.user'])
            ->where('status', EventStatus::Past)
            ->get();

        foreach ($pastEvents as $event) {
            // Get attendees (those who actually attended)
            $attendees = $event->rsvps
                ->where('attended', AttendanceResult::Attended);

            if ($attendees->isEmpty()) {
                continue;
            }

            // ~60-80% of attendees leave feedback
            $feedbackCount = (int) ceil($attendees->count() * fake()->randomFloat(2, 0.6, 0.8));
            $feedbackGivers = $attendees->random(min($feedbackCount, $attendees->count()));

            foreach ($feedbackGivers as $rsvp) {
                // Mostly 4-5 stars, some 3s, rare 1-2s
                $rating = fake()->randomElement([4, 4, 4, 5, 5, 5, 5, 3, 3, 5]);

                // ~50% include written text
                $body = fake()->boolean(50) ? fake()->randomElement(self::FEEDBACK_TEXTS) : null;

                Feedback::create([
                    'event_id' => $event->id,
                    'user_id' => $rsvp->user_id,
                    'rating' => $rating,
                    'body' => $body,
                ]);
            }
        }
    }
}
