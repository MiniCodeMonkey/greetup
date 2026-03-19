<?php

namespace Database\Factories;

use App\Models\Comment;
use App\Models\Event;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Comment>
 */
class CommentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $body = fake()->paragraph();

        return [
            'event_id' => Event::factory(),
            'user_id' => User::factory(),
            'parent_id' => null,
            'body' => $body,
            'body_html' => '<p>'.$body.'</p>',
        ];
    }

    /**
     * Create a reply to a parent comment.
     */
    public function reply(?Comment $parent = null): static
    {
        return $this->state(fn (array $attributes) => [
            'parent_id' => $parent?->id ?? Comment::factory(),
            'event_id' => $parent?->event_id ?? $attributes['event_id'],
        ]);
    }
}
