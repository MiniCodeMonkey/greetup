<?php

namespace Database\Factories;

use App\Models\Conversation;
use App\Models\ConversationParticipant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ConversationParticipant>
 */
class ConversationParticipantFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'conversation_id' => Conversation::factory(),
            'user_id' => User::factory(),
            'last_read_at' => null,
            'is_muted' => false,
        ];
    }

    /**
     * Indicate that the participant has muted the conversation.
     */
    public function muted(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_muted' => true,
        ]);
    }
}
