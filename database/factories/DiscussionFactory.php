<?php

namespace Database\Factories;

use App\Models\Discussion;
use App\Models\Group;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Discussion>
 */
class DiscussionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'group_id' => Group::factory(),
            'user_id' => User::factory(),
            'title' => fake()->sentence(),
            'body' => fake()->paragraphs(3, true),
            'body_html' => null,
            'is_pinned' => false,
            'is_locked' => false,
            'last_activity_at' => now(),
        ];
    }

    /**
     * Indicate the discussion is pinned.
     */
    public function pinned(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_pinned' => true,
        ]);
    }

    /**
     * Indicate the discussion is locked.
     */
    public function locked(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_locked' => true,
        ]);
    }
}
