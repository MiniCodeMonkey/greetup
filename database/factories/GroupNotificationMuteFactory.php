<?php

namespace Database\Factories;

use App\Models\Group;
use App\Models\GroupNotificationMute;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GroupNotificationMute>
 */
class GroupNotificationMuteFactory extends Factory
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
            'group_id' => Group::factory(),
            'created_at' => now(),
        ];
    }
}
