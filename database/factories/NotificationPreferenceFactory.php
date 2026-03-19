<?php

namespace Database\Factories;

use App\Enums\NotificationChannel;
use App\Models\NotificationPreference;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<NotificationPreference>
 */
class NotificationPreferenceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'channel' => fake()->randomElement(NotificationChannel::cases()),
            'type' => 'App\\Notifications\\'.fake()->word().'Notification',
            'enabled' => true,
        ];
    }

    /**
     * Set the preference as disabled.
     */
    public function disabled(): static
    {
        return $this->state(fn (array $attributes) => [
            'enabled' => false,
        ]);
    }
}
