<?php

namespace Database\Factories;

use App\Models\PendingNotificationDigest;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PendingNotificationDigest>
 */
class PendingNotificationDigestFactory extends Factory
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
            'notification_type' => 'App\\Notifications\\'.fake()->word().'Notification',
            'data' => ['message' => fake()->sentence()],
            'created_at' => now(),
        ];
    }
}
