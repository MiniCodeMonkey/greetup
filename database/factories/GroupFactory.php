<?php

namespace Database\Factories;

use App\Enums\GroupVisibility;
use App\Models\Group;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Group>
 */
class GroupFactory extends Factory
{
    /**
     * Hardcoded locations for realistic data.
     *
     * @var array<int, array{city: string, lat: float, lng: float, tz: string}>
     */
    private const LOCATIONS = [
        ['city' => 'Copenhagen, Denmark', 'lat' => 55.6761000, 'lng' => 12.5683000, 'tz' => 'Europe/Copenhagen'],
        ['city' => 'Berlin, Germany', 'lat' => 52.5200000, 'lng' => 13.4050000, 'tz' => 'Europe/Berlin'],
        ['city' => 'London, UK', 'lat' => 51.5074000, 'lng' => -0.1278000, 'tz' => 'Europe/London'],
        ['city' => 'New York, NY', 'lat' => 40.7128000, 'lng' => -74.0060000, 'tz' => 'America/New_York'],
        ['city' => 'San Francisco, CA', 'lat' => 37.7749000, 'lng' => -122.4194000, 'tz' => 'America/Los_Angeles'],
        ['city' => 'Tokyo, Japan', 'lat' => 35.6762000, 'lng' => 139.6503000, 'tz' => 'Asia/Tokyo'],
    ];

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $location = fake()->randomElement(self::LOCATIONS);
        $description = fake()->paragraphs(2, true);

        return [
            'name' => fake()->company().' '.fake()->randomElement(['Meetup', 'Club', 'Group', 'Community', 'Society']),
            'description' => $description,
            'description_html' => '<p>'.nl2br(e($description)).'</p>',
            'organizer_id' => User::factory(),
            'location' => $location['city'],
            'latitude' => $location['lat'],
            'longitude' => $location['lng'],
            'timezone' => $location['tz'],
            'visibility' => GroupVisibility::Public,
            'requires_approval' => false,
            'max_members' => fake()->optional(0.3)->numberBetween(20, 200),
            'welcome_message' => fake()->optional(0.5)->sentence(),
            'is_active' => true,
        ];
    }

    /**
     * Indicate the group is private.
     */
    public function private(): static
    {
        return $this->state(fn (array $attributes): array => [
            'visibility' => GroupVisibility::Private,
        ]);
    }

    /**
     * Indicate the group requires approval to join.
     */
    public function requiresApproval(): static
    {
        return $this->state(fn (array $attributes): array => [
            'requires_approval' => true,
        ]);
    }

    /**
     * Indicate the group is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_active' => false,
        ]);
    }
}
