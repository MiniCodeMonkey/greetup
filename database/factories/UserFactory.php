<?php

namespace Database\Factories;

use App\Enums\ProfileVisibility;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Known locations with realistic coordinates.
     *
     * @var list<array{city: string, latitude: float, longitude: float, timezone: string}>
     */
    protected static array $locations = [
        ['city' => 'Copenhagen, Denmark', 'latitude' => 55.6761000, 'longitude' => 12.5683000, 'timezone' => 'Europe/Copenhagen'],
        ['city' => 'Berlin, Germany', 'latitude' => 52.5200000, 'longitude' => 13.4050000, 'timezone' => 'Europe/Berlin'],
        ['city' => 'London, United Kingdom', 'latitude' => 51.5074000, 'longitude' => -0.1278000, 'timezone' => 'Europe/London'],
        ['city' => 'New York, NY', 'latitude' => 40.7128000, 'longitude' => -74.0060000, 'timezone' => 'America/New_York'],
    ];

    /**
     * @var list<string>
     */
    protected static array $interests = [
        'Web Development',
        'Machine Learning',
        'Photography',
        'Hiking',
        'Board Games',
        'Cooking',
        'Music',
        'Yoga',
        'Running',
        'Reading',
        'Cycling',
        'Design',
        'Startups',
        'Open Source',
        'Data Science',
    ];

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $location = fake()->randomElement(static::$locations);

        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'bio' => fake()->optional(0.8)->paragraph(),
            'location' => $location['city'],
            'latitude' => $location['latitude'],
            'longitude' => $location['longitude'],
            'timezone' => $location['timezone'],
            'looking_for' => fake()->randomElements(
                ['practicing hobbies', 'making friends', 'networking', 'learning new things', 'professional development'],
                fake()->numberBetween(1, 3),
            ),
            'profile_visibility' => ProfileVisibility::Public,
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Configure the model factory.
     */
    public function configure(): static
    {
        return $this->afterCreating(function (User $user): void {
            $interests = fake()->randomElements(
                static::$interests,
                fake()->numberBetween(3, 8),
            );

            $user->attachTags($interests, 'interest');
        });
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    /**
     * Indicate that the user account is suspended.
     */
    public function suspended(string $reason = 'Violation of community guidelines'): static
    {
        return $this->state(fn (array $attributes) => [
            'is_suspended' => true,
            'suspended_at' => now(),
            'suspended_reason' => $reason,
        ]);
    }

    /**
     * Indicate that the user is an admin.
     */
    public function admin(): static
    {
        return $this->afterCreating(function (User $user): void {
            $user->assignRole('admin');
        });
    }
}
