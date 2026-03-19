<?php

namespace Database\Factories;

use App\Enums\EventStatus;
use App\Enums\EventType;
use App\Models\Event;
use App\Models\Group;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Event>
 */
class EventFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startsAt = fake()->dateTimeBetween('+1 day', '+3 months');
        $description = fake()->paragraphs(2, true);

        return [
            'group_id' => Group::factory(),
            'created_by' => User::factory(),
            'name' => fake()->randomElement(['Weekly', 'Monthly', 'Annual', 'Special']).' '.fake()->randomElement(['Meetup', 'Workshop', 'Hackathon', 'Conference', 'Social']),
            'description' => $description,
            'description_html' => '<p>'.nl2br(e($description)).'</p>',
            'event_type' => EventType::InPerson,
            'status' => EventStatus::Draft,
            'starts_at' => $startsAt,
            'ends_at' => fake()->optional(0.7)->dateTimeBetween($startsAt, (clone $startsAt)->modify('+4 hours')),
            'timezone' => fake()->randomElement(['America/New_York', 'Europe/London', 'Europe/Berlin', 'Asia/Tokyo', 'America/Los_Angeles']),
            'venue_name' => fake()->optional(0.8)->company(),
            'venue_address' => fake()->optional(0.8)->address(),
            'venue_latitude' => fake()->optional(0.8)->latitude(-90, 90),
            'venue_longitude' => fake()->optional(0.8)->longitude(-180, 180),
            'online_link' => null,
            'rsvp_limit' => fake()->optional(0.3)->numberBetween(10, 200),
            'guest_limit' => 0,
            'is_chat_enabled' => true,
            'is_comments_enabled' => true,
        ];
    }

    /**
     * Indicate the event is a draft.
     */
    public function draft(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => EventStatus::Draft,
        ]);
    }

    /**
     * Indicate the event is published.
     */
    public function published(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => EventStatus::Published,
        ]);
    }

    /**
     * Indicate the event is cancelled.
     */
    public function cancelled(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => EventStatus::Cancelled,
            'cancelled_at' => now(),
            'cancellation_reason' => fake()->sentence(),
        ]);
    }

    /**
     * Indicate the event is in the past.
     */
    public function past(): static
    {
        $startsAt = fake()->dateTimeBetween('-3 months', '-1 day');

        return $this->state(fn (array $attributes): array => [
            'status' => EventStatus::Past,
            'starts_at' => $startsAt,
            'ends_at' => (clone $startsAt)->modify('+2 hours'),
        ]);
    }

    /**
     * Indicate the event is online.
     */
    public function online(): static
    {
        return $this->state(fn (array $attributes): array => [
            'event_type' => EventType::Online,
            'venue_name' => null,
            'venue_address' => null,
            'venue_latitude' => null,
            'venue_longitude' => null,
            'online_link' => fake()->url(),
        ]);
    }

    /**
     * Indicate the event is hybrid.
     */
    public function hybrid(): static
    {
        return $this->state(fn (array $attributes): array => [
            'event_type' => EventType::Hybrid,
            'online_link' => fake()->url(),
        ]);
    }

    /**
     * Set the RSVP limit.
     */
    public function withRsvpLimit(int $limit): static
    {
        return $this->state(fn (array $attributes): array => [
            'rsvp_limit' => $limit,
        ]);
    }
}
