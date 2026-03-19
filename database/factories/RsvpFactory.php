<?php

namespace Database\Factories;

use App\Enums\AttendanceResult;
use App\Enums\RsvpStatus;
use App\Models\Event;
use App\Models\Rsvp;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Rsvp>
 */
class RsvpFactory extends Factory
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
            'status' => RsvpStatus::Going,
            'guest_count' => 0,
            'attendance_mode' => null,
            'checked_in' => false,
            'checked_in_at' => null,
            'checked_in_by' => null,
            'attended' => null,
            'waitlisted_at' => null,
        ];
    }

    /**
     * Indicate the RSVP status is going.
     */
    public function going(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => RsvpStatus::Going,
        ]);
    }

    /**
     * Indicate the RSVP status is waitlisted.
     */
    public function waitlisted(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => RsvpStatus::Waitlisted,
            'waitlisted_at' => now(),
        ]);
    }

    /**
     * Indicate the RSVP status is not going.
     */
    public function notGoing(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => RsvpStatus::NotGoing,
        ]);
    }

    /**
     * Indicate the RSVP has been checked in.
     */
    public function checkedIn(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => RsvpStatus::Going,
            'checked_in' => true,
            'checked_in_at' => now(),
            'checked_in_by' => User::factory(),
            'attended' => AttendanceResult::Attended,
        ]);
    }

    /**
     * Set the guest count.
     */
    public function withGuests(int $count): static
    {
        return $this->state(fn (array $attributes): array => [
            'guest_count' => $count,
        ]);
    }
}
