<?php

namespace Database\Factories;

use App\Models\Event;
use App\Models\EventChatMessage;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EventChatMessage>
 */
class EventChatMessageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'event_id' => Event::factory(),
            'user_id' => User::factory(),
            'body' => fake()->sentence(),
            'reply_to_id' => null,
        ];
    }

    /**
     * Create a reply to a parent message.
     */
    public function replyTo(?EventChatMessage $parent = null): static
    {
        return $this->state(fn (array $attributes) => [
            'reply_to_id' => $parent?->id ?? EventChatMessage::factory(),
            'event_id' => $parent?->event_id ?? $attributes['event_id'],
        ]);
    }
}
