<?php

namespace Database\Factories;

use App\Models\EventSeries;
use App\Models\Group;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EventSeries>
 */
class EventSeriesFactory extends Factory
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
            'recurrence_rule' => fake()->randomElement([
                'FREQ=WEEKLY;BYDAY=TU',
                'FREQ=WEEKLY;BYDAY=WE',
                'FREQ=MONTHLY;BYDAY=1MO',
                'FREQ=BIWEEKLY;BYDAY=TH',
            ]),
        ];
    }
}
