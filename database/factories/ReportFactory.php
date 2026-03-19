<?php

namespace Database\Factories;

use App\Enums\ReportReason;
use App\Enums\ReportStatus;
use App\Models\Comment;
use App\Models\Report;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Report>
 */
class ReportFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'reporter_id' => User::factory(),
            'reportable_type' => Comment::class,
            'reportable_id' => Comment::factory(),
            'reason' => fake()->randomElement(ReportReason::cases()),
            'description' => fake()->optional()->sentence(),
            'status' => ReportStatus::Pending,
        ];
    }

    /**
     * Set the report status to reviewed.
     */
    public function reviewed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ReportStatus::Reviewed,
            'reviewed_by' => User::factory(),
            'reviewed_at' => now(),
        ]);
    }

    /**
     * Set the report status to resolved.
     */
    public function resolved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ReportStatus::Resolved,
            'reviewed_by' => User::factory(),
            'reviewed_at' => now(),
            'resolution_notes' => fake()->sentence(),
        ]);
    }

    /**
     * Set the report status to dismissed.
     */
    public function dismissed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ReportStatus::Dismissed,
            'reviewed_by' => User::factory(),
            'reviewed_at' => now(),
            'resolution_notes' => fake()->sentence(),
        ]);
    }
}
