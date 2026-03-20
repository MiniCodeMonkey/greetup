<?php

namespace Database\Seeders;

use App\Enums\EventStatus;
use App\Enums\EventType;
use App\Models\Event;
use App\Models\EventSeries;
use App\Models\Group;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class EventSeeder extends Seeder
{
    /**
     * Realistic venue data per city.
     *
     * @var array<string, list<array{name: string, address: string, lat: float, lng: float}>>
     */
    private const array VENUES = [
        'Copenhagen' => [
            ['name' => 'Copenhagen Tech Hub', 'address' => 'Nørregade 7A, 1165 Copenhagen K', 'lat' => 55.6800, 'lng' => 12.5720],
            ['name' => 'Café Norden', 'address' => 'Østergade 61, 1100 Copenhagen K', 'lat' => 55.6785, 'lng' => 12.5780],
            ['name' => 'Dome of Visions', 'address' => 'Poul Henningsens Plads 1, 2100 Copenhagen', 'lat' => 55.7040, 'lng' => 12.5870],
            ['name' => 'Bastard Café', 'address' => 'Rådhusstræde 13, 1466 Copenhagen K', 'lat' => 55.6760, 'lng' => 12.5730],
            ['name' => 'Founders House', 'address' => 'Njalsgade 19D, 2300 Copenhagen S', 'lat' => 55.6630, 'lng' => 12.5880],
        ],
        'Berlin' => [
            ['name' => 'betahaus Berlin', 'address' => 'Rudi-Dutschke-Straße 23, 10969 Berlin', 'lat' => 52.5070, 'lng' => 13.3920],
            ['name' => 'Factory Berlin Mitte', 'address' => 'Rheinsberger Str. 76/77, 10115 Berlin', 'lat' => 52.5340, 'lng' => 13.3980],
            ['name' => 'St. Oberholz', 'address' => 'Rosenthaler Str. 72A, 10119 Berlin', 'lat' => 52.5290, 'lng' => 13.4010],
        ],
        'London' => [
            ['name' => 'The Book Club Shoreditch', 'address' => '100-106 Leonard St, London EC2A 4RH', 'lat' => 51.5270, 'lng' => -0.0820],
            ['name' => 'Waterstones Piccadilly', 'address' => '203-206 Piccadilly, London W1J 9HD', 'lat' => 51.5080, 'lng' => -0.1370],
            ['name' => 'WeWork Moorgate', 'address' => '1 Fore St Ave, London EC2Y 9DT', 'lat' => 51.5190, 'lng' => -0.0910],
        ],
        'New York' => [
            ['name' => 'The Cliffs at LIC', 'address' => '11-11 44th Dr, Long Island City, NY 11101', 'lat' => 40.7470, 'lng' => -73.9520],
            ['name' => 'Central Park Boathouse', 'address' => 'E 72nd St, New York, NY 10021', 'lat' => 40.7750, 'lng' => -73.9680],
            ['name' => 'Harriman State Park', 'address' => 'Harriman State Park, Stony Point, NY 10980', 'lat' => 41.2510, 'lng' => -74.0860],
        ],
    ];

    /**
     * Event name templates per group category.
     *
     * @var array<string, list<string>>
     */
    private const array EVENT_NAMES = [
        'tech' => [
            'Deep Dive: %s',
            'Lightning Talks Night',
            'Workshop: Getting Started with %s',
            'Code & Coffee Morning',
            'Hackathon: Build Something Cool',
            'Panel: The Future of %s',
            'Pair Programming Session',
            'Ask Me Anything with %s',
        ],
        'lifestyle' => [
            'Monthly %s Meetup',
            'Weekend %s Session',
            'Social %s Night',
            'Beginner-Friendly %s',
            'Special Edition: %s',
        ],
        'creative' => [
            '%s Walk: %s Edition',
            'Critique & Share Session',
            'Monthly %s Challenge',
            'Exhibition Night',
            'Guest Speaker: %s',
        ],
        'professional' => [
            'Networking Lunch',
            'Skill Share: %s',
            'Co-working Day at %s',
            'Monthly Mixer',
            'Workshop: %s for Beginners',
        ],
    ];

    /**
     * Group categories mapped by group name prefix.
     *
     * @var array<string, string>
     */
    private const array GROUP_CATEGORIES = [
        'Copenhagen Laravel' => 'tech',
        'Berlin JavaScript' => 'tech',
        'London Book' => 'lifestyle',
        'NYC Hiking' => 'lifestyle',
        'Copenhagen Photography' => 'creative',
        'Remote Workers' => 'professional',
        'Board Game' => 'lifestyle',
        'Women in Tech' => 'tech',
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $groups = Group::with('members')->get();

        foreach ($groups as $group) {
            $category = $this->getGroupCategory($group->name);
            $venueCity = $this->getVenueCity($group->location);
            $venues = self::VENUES[$venueCity] ?? self::VENUES['Copenhagen'];
            $organizer = $group->organizer;

            // 2-3 past events (1-6 months ago)
            $pastCount = fake()->numberBetween(2, 3);
            for ($i = 0; $i < $pastCount; $i++) {
                $startsAt = Carbon::now()->subMonths(rand(1, 6))->subDays(rand(0, 15))->setHour(rand(17, 19))->setMinute(0)->setSecond(0);
                $venue = fake()->randomElement($venues);

                Event::create([
                    'group_id' => $group->id,
                    'created_by' => $organizer->id,
                    'name' => $this->generateEventName($category, $group->name),
                    'description' => $this->generateDescription($category),
                    'description_html' => '<p>'.fake()->paragraphs(2, true).'</p>',
                    'event_type' => EventType::InPerson,
                    'status' => EventStatus::Past,
                    'starts_at' => $startsAt,
                    'ends_at' => (clone $startsAt)->addHours(2),
                    'timezone' => $group->timezone,
                    'venue_name' => $venue['name'],
                    'venue_address' => $venue['address'],
                    'venue_latitude' => $venue['lat'],
                    'venue_longitude' => $venue['lng'],
                    'rsvp_limit' => fake()->optional(0.4)->numberBetween(15, 40),
                    'guest_limit' => fake()->randomElement([0, 0, 1, 2]),
                    'is_chat_enabled' => true,
                    'is_comments_enabled' => true,
                ]);
            }

            // 2-3 upcoming events (next 1-4 weeks)
            $upcomingCount = fake()->numberBetween(2, 3);
            for ($i = 0; $i < $upcomingCount; $i++) {
                $startsAt = Carbon::now()->addDays(rand(3, 28))->setHour(rand(17, 19))->setMinute(0)->setSecond(0);
                $venue = fake()->randomElement($venues);

                Event::create([
                    'group_id' => $group->id,
                    'created_by' => $organizer->id,
                    'name' => $this->generateEventName($category, $group->name),
                    'description' => $this->generateDescription($category),
                    'description_html' => '<p>'.fake()->paragraphs(2, true).'</p>',
                    'event_type' => fake()->randomElement([EventType::InPerson, EventType::InPerson, EventType::Hybrid]),
                    'status' => EventStatus::Published,
                    'starts_at' => $startsAt,
                    'ends_at' => (clone $startsAt)->addHours(rand(2, 3)),
                    'timezone' => $group->timezone,
                    'venue_name' => $venue['name'],
                    'venue_address' => $venue['address'],
                    'venue_latitude' => $venue['lat'],
                    'venue_longitude' => $venue['lng'],
                    'online_link' => fake()->optional(0.3)->url(),
                    'rsvp_limit' => fake()->optional(0.3)->numberBetween(15, 40),
                    'guest_limit' => fake()->randomElement([0, 0, 1, 2]),
                    'is_chat_enabled' => true,
                    'is_comments_enabled' => true,
                ]);
            }

            // 1 draft event
            $startsAt = Carbon::now()->addDays(rand(30, 60))->setHour(18)->setMinute(0)->setSecond(0);
            Event::create([
                'group_id' => $group->id,
                'created_by' => $organizer->id,
                'name' => 'Draft: '.$this->generateEventName($category, $group->name),
                'description' => fake()->paragraph(),
                'description_html' => '<p>'.fake()->paragraph().'</p>',
                'event_type' => EventType::InPerson,
                'status' => EventStatus::Draft,
                'starts_at' => $startsAt,
                'ends_at' => (clone $startsAt)->addHours(2),
                'timezone' => $group->timezone,
                'venue_name' => fake()->randomElement($venues)['name'],
                'venue_address' => fake()->randomElement($venues)['address'],
                'venue_latitude' => fake()->randomElement($venues)['lat'],
                'venue_longitude' => fake()->randomElement($venues)['lng'],
                'is_chat_enabled' => true,
                'is_comments_enabled' => true,
            ]);

            // 1 cancelled event
            $startsAt = Carbon::now()->addDays(rand(5, 20))->setHour(18)->setMinute(0)->setSecond(0);
            Event::create([
                'group_id' => $group->id,
                'created_by' => $organizer->id,
                'name' => $this->generateEventName($category, $group->name),
                'description' => fake()->paragraph(),
                'description_html' => '<p>'.fake()->paragraph().'</p>',
                'event_type' => EventType::InPerson,
                'status' => EventStatus::Cancelled,
                'starts_at' => $startsAt,
                'ends_at' => (clone $startsAt)->addHours(2),
                'timezone' => $group->timezone,
                'venue_name' => fake()->randomElement($venues)['name'],
                'venue_address' => fake()->randomElement($venues)['address'],
                'venue_latitude' => fake()->randomElement($venues)['lat'],
                'venue_longitude' => fake()->randomElement($venues)['lng'],
                'cancelled_at' => now()->subDays(rand(1, 3)),
                'cancellation_reason' => fake()->randomElement([
                    'Venue unavailable due to maintenance.',
                    'Speaker had to cancel — we will reschedule soon!',
                    'Not enough RSVPs to justify running this event.',
                ]),
                'is_chat_enabled' => true,
                'is_comments_enabled' => true,
            ]);

            // 1 recurring series with 4+ instances
            $series = EventSeries::create([
                'group_id' => $group->id,
                'recurrence_rule' => 'FREQ=WEEKLY;BYDAY='.fake()->randomElement(['TU', 'WE', 'TH']),
            ]);

            $seriesBaseName = $this->getSeriesName($category, $group->name);
            $baseDate = Carbon::now()->subWeeks(6)->next(Carbon::TUESDAY)->setHour(18)->setMinute(0)->setSecond(0);

            for ($i = 0; $i < 5; $i++) {
                $instanceDate = (clone $baseDate)->addWeeks($i);
                $isPast = $instanceDate->isPast();

                Event::create([
                    'group_id' => $group->id,
                    'created_by' => $organizer->id,
                    'series_id' => $series->id,
                    'name' => $seriesBaseName.' #'.($i + 1),
                    'description' => "Part of our recurring weekly series. Join us every week!\n\n".fake()->paragraph(),
                    'description_html' => '<p>Part of our recurring weekly series.</p><p>'.fake()->paragraph().'</p>',
                    'event_type' => EventType::InPerson,
                    'status' => $isPast ? EventStatus::Past : EventStatus::Published,
                    'starts_at' => $instanceDate,
                    'ends_at' => (clone $instanceDate)->addHours(2),
                    'timezone' => $group->timezone,
                    'venue_name' => $venues[0]['name'],
                    'venue_address' => $venues[0]['address'],
                    'venue_latitude' => $venues[0]['lat'],
                    'venue_longitude' => $venues[0]['lng'],
                    'rsvp_limit' => fake()->optional(0.3)->numberBetween(15, 30),
                    'guest_limit' => 1,
                    'is_chat_enabled' => true,
                    'is_comments_enabled' => true,
                ]);
            }
        }

        // Ensure at least 2 events are at capacity with RSVP limits set low
        $upcomingPublished = Event::where('status', EventStatus::Published)
            ->whereNull('rsvp_limit')
            ->limit(2)
            ->get();

        foreach ($upcomingPublished as $event) {
            $event->update(['rsvp_limit' => 10]);
        }
    }

    private function getGroupCategory(string $groupName): string
    {
        foreach (self::GROUP_CATEGORIES as $prefix => $category) {
            if (str_starts_with($groupName, $prefix)) {
                return $category;
            }
        }

        return 'lifestyle';
    }

    private function getVenueCity(string $location): string
    {
        if (str_contains($location, 'Copenhagen')) {
            return 'Copenhagen';
        }
        if (str_contains($location, 'Berlin')) {
            return 'Berlin';
        }
        if (str_contains($location, 'London')) {
            return 'London';
        }
        if (str_contains($location, 'New York')) {
            return 'New York';
        }

        return 'Copenhagen';
    }

    private function generateEventName(string $category, string $groupName): string
    {
        $templates = self::EVENT_NAMES[$category] ?? self::EVENT_NAMES['lifestyle'];
        $template = fake()->randomElement($templates);
        $topics = ['APIs', 'Testing', 'Performance', 'Architecture', 'AI/ML', 'Docker', 'TypeScript', 'Rust'];
        $places = ['Nørrebro', 'Kreuzberg', 'Shoreditch', 'Brooklyn', 'the Waterfront', 'the Old Town'];

        // Count placeholders and provide enough arguments
        $count = substr_count($template, '%s');

        return match ($count) {
            0 => $template,
            1 => sprintf($template, fake()->randomElement($topics)),
            default => sprintf($template, fake()->randomElement($topics), fake()->randomElement($places)),
        };
    }

    private function generateDescription(string $category): string
    {
        return "Join us for an exciting session!\n\n".fake()->paragraphs(2, true)."\n\n**What to bring:** Your laptop and enthusiasm!";
    }

    private function getSeriesName(string $category, string $groupName): string
    {
        return match ($category) {
            'tech' => 'Weekly Code & Chat',
            'creative' => 'Weekly Photo Walk',
            'professional' => 'Weekly Co-working Session',
            default => 'Weekly Meetup',
        };
    }
}
